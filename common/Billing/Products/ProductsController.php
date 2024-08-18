<?php namespace Common\Billing\Products;

use Common\Billing\Gateways\Paypal\Paypal;
use Common\Billing\Gateways\Stripe\Stripe;
use Common\Billing\Models\Product;
use Common\Billing\Products\Actions\CrupdateProduct;
use Common\Core\BaseController;
use Common\Database\Datasource\Datasource;
use Exception;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductsController extends BaseController
{
    public function __construct(
        protected Stripe $stripe,
        protected Paypal $paypal
    ) {
    }

    public function index()
    {
        $this->authorize('index', Product::class);

        $dataSource = new Datasource(
            Product::with(['permissions', 'prices']),
            request()->all(),
        );
        $dataSource->order = ['col' => 'position', 'dir' => 'asc'];

        return $this->success(['pagination' => $dataSource->paginate()]);
    }

    public function show(Product $product)
    {
        $this->authorize('show', $product);

        $product->load([
            'permissions',
            'prices' => fn(HasMany $builder) => $builder->withCount(
            'subscriptions',
            ),
        ]);

        return ['product' => $product];
    }

    public function store()
    {
        $this->authorize('store', Product::class);

        $this->validate(request(), [
            'name' => 'required|string|max:250',
            'permissions' => 'array',
            'recommended' => 'boolean',
            'position' => 'integer',
            'available_space' => 'nullable|integer|min:1',
            'prices' => ['array', Rule::requiredIf(!request('free'))],
            'prices.*.currency' => 'required|string|max:255',
            'prices.*.interval' => 'string|max:255',
            'prices.*.amount' => 'min:1',
        ]);

        $plan = app(CrupdateProduct::class)->execute(request()->all());

        return $this->success(['plan' => $plan]);
    }

    public function update(Product $product)
    {
        $this->authorize('update', $product);

        $this->validate(request(), [
            'name' => 'required|string|max:250',
            'permissions' => 'array',
            'recommended' => 'boolean',
            'prices' => ['array', Rule::requiredIf(!request('free'))],
            'prices.*.currency' => 'required|string|max:255',
            'prices.*.interval' => 'string|max:255',
            'prices.*.amount' => 'min:1',
        ]);

        $product = app(CrupdateProduct::class)->execute(
            request()->all(),
            $product,
        );

        return $this->success(['product' => $product]);
    }

    public function destroy(Product $product): Response|JsonResponse
    {
        $this->authorize('destroy', $product);

        if ($product->subscriptions_count) {
            return $this->error(
                __(
                    "Could not delete ':plan', because it has active subscriptions.",
                    ['plan' => $product->name],
                ),
            );
        }

        try {
            if ($this->stripe->isEnabled()) {
                $this->stripe->deletePlan($product);
            }
            if ($this->paypal->isEnabled()) {
                $this->paypal->deletePlan($product);
            }
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }

        $product->delete();

        return $this->success();
    }





    //admin 

    public function indexApi()
    {
        $products = Product::with(['prices' => function($query) {
            $query->select('id', 'product_id', 'amount as pricing' , 'currency' , 'interval'); 
        }])->get()->map(function($product) {
            $product->prices = $product->prices->map(function($price) {
                return ['pricing' => $price->amount];
            });
            return $product;
        });
        return response()->json(['products' => $products], 200);
    }
    
    public function showApi($id)
    {
        $product = Product::with(['prices' => function($query) {
            $query->select('id', 'product_id', 'amount'  , 'currency' , 'interval'); 
        }])->find($id);
    
        if (!$product) {
            return response()->json([
                'message' => 'Product not found'
            ], 404);
        }
            return response()->json(['product' => $product], 200);
    }

    public function storeApi(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:250',
            'permissions' => 'array',
            'recommended' => 'boolean',
            'position' => 'integer',
            'available_space' => 'nullable|integer|min:1',
            'prices' => ['array', Rule::requiredIf(!$request->input('free'))],
            'prices.*.currency' => 'required|string|max:255',
            'prices.*.interval' => 'string|max:255',
            'prices.*.amount' => 'numeric|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
                'message' => 'Validation failed' 
            ], 422); 
        }

        $product = Product::create([
            'name' => $request->name,
            'description' => $request->description,
            'hidden' => $request->hidden ?? false,
            'free' => $request->free ?? false,
            'recommended' => $request->recommended ?? false,
            'position' => $request->position ?? 0,
            'available_space' => $request->available_space ?? null,
            'feature_list' => $request->feature_list ?? [],
        ]);

        if (isset($request->permissions) && is_array($request->permissions)) {
            $product->permissions()->sync($request->permissions);
        }
    
        if (isset($request->prices) && is_array($request->prices)) {
            foreach ($request->prices as $price) {
                $product->prices()->create([
                    'amount' => $price['amount'],
                    'interval_count' => $price['interval_count'],
                    'interval' => $price['interval'],
                    'currency' => $price['currency'],
                ]);
            }
        }

        return response()->json([
                'data' => $product,
                'message' => 'Product created successfully' // message
            ], 201);   
    }

    public function updateApi(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:250',
            'permissions' => 'array',
            'recommended' => 'boolean',
            'position' => 'integer',
            'available_space' => 'nullable|integer|min:1',
            'prices' => ['array', Rule::requiredIf(!$request->input('free'))],
            'prices.*.currency' => 'required|string|max:255',
            'prices.*.interval' => 'string|max:255',
            'prices.*.amount' => 'numeric|min:1',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
                'message' => 'Validation failed'
            ], 422);
        }
    
        $product = Product::find($id);
    
        if (!$product) {
            return response()->json([
                'message' => 'Product not found'
            ], 404);
        }
    
        $product->update([
            'name' => $request->name,
            'description' => $request->description,
            'hidden' => $request->hidden ?? false,
            'free' => $request->free ?? false,
            'recommended' => $request->recommended ?? false,
            'position' => $request->position ?? 0,
            'available_space' => $request->available_space ?? null,
            'feature_list' => $request->feature_list ?? [],
        ]);
    
        if (isset($request->permissions) && is_array($request->permissions)) {
            $product->permissions()->sync($request->permissions);
        }
    
        if (isset($request->prices) && is_array($request->prices)) {
            // حذف الأسعار القديمة
            $product->prices()->delete();
    
            // إضافة الأسعار الجديدة
            foreach ($request->prices as $price) {
                $product->prices()->create([
                    'amount' => $price['amount'],
                    'interval_count' => $price['interval_count'],
                    'interval' => $price['interval'],
                    'currency' => $price['currency'],
                ]);
            }
        }
    
        return response()->json([
            'data' => $product,
            'message' => 'Product updated successfully'
        ], 200);
    }
    

    public function deleteApi($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'message' => 'Tag not found'
            ], 404);
        }

        $product->delete();

        return response()->json(['message' => 'Product deleted successfully'], 200);
    }

}
