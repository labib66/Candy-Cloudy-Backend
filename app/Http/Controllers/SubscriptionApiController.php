<?php

namespace App\Http\Controllers;

use App\Http\Resources\SubscriptionResource;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubscriptionApiController extends Controller
{
  //
  public function indexApi()
  {
    // return response()->json(['message' => 'API is working']);
    $Subscriptions = Subscription::with('user')->get();
    return SubscriptionResource::collection($Subscriptions);
  }

  public function showApi($id)
  {
      $Subscription = Subscription::find($id);

      if (!$Subscription) {
          return response()->json([
              'status' => 'error',
              'message' => 'Page not found',
          ], 404);
      }

      return response()->json([
          'status' => 'success',
          'data' => new SubscriptionResource($Subscription),
      ]);
  }

  public function storeApi(Request $request)
  {

    $validator = Validator::make($request->all(), [
      'user_id' => 'required|integer',
      'plan_id' => 'required|integer',
      'gateway_name' => 'required|string',
      'gateway_id' => 'nullable|string',
      'quantity' => 'required|integer',
      'description' => 'nullable|string',
      'trial_ends_at' => 'nullable|date',
      'ends_at' => 'nullable|date',
      'renews_at' => 'nullable|date',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'status' => 'error',
        'message' => 'Validation failed',
        'errors' => $validator->errors(),
      ], 422);
    }

    //create
    $subscription = Subscription::create([
      'user_id' => $request->user_id,
      'plan_id' => $request->plan_id,
      'gateway_name' => $request->gateway_name,
      'gateway_id' => $request->gateway_id,
      'quantity' => $request->quantity,
      'description' => $request->description,
      'trial_ends_at' => $request->trial_ends_at,
      'ends_at' => $request->ends_at,
      'renews_at' => $request->renews_at,
    ]);

    return response()->json([
      'status' => 'success',
      'message' => 'Subscription created successfully',
      'data' => $subscription,
    ], 201);
  }

  public function updateApi(Request $request, $id)
  {
      $validator = Validator::make($request->all(), [
          'user_id' => 'required|integer',
          'plan_id' => 'required|integer',
          'gateway_name' => 'required|string',
          'gateway_id' => 'nullable|string',
          'quantity' => 'required|integer',
          'description' => 'nullable|string',
          'trial_ends_at' => 'nullable|date',
          'ends_at' => 'nullable|date',
          'renews_at' => 'nullable|date',
      ]);
  
      if ($validator->fails()) {
          return response()->json([
              'status' => 'error',
              'message' => 'Validation failed',
              'errors' => $validator->errors(),
          ], 422);
      }
  
      $Subscription = Subscription::find($id);
  
      if (!$Subscription) {
          return response()->json([
              'error' => 'Subscription not found'
          ], 404);
      }
  
      // Update the subscription
      $Subscription->update([
          'user_id' => $request->user_id,
          'plan_id' => $request->plan_id,
          'gateway_name' => $request->gateway_name,
          'gateway_id' => $request->gateway_id,
          'quantity' => $request->quantity,
          'description' => $request->description,
          'trial_ends_at' => $request->trial_ends_at,
          'ends_at' => $request->ends_at,
          'renews_at' => $request->renews_at,
      ]);
  
      // Return a success response
      return response()->json([
          'success' => 'Subscription updated successfully',
          'data' => new SubscriptionResource($Subscription)
      ]);
  }
  



  public function deleteApi($id)
  {
    $Subscription = Subscription::find($id);
    if ($Subscription === null) {
      return response()->json([
        "error" => "Subscription not found"
      ], 404);
    }
    $Subscription->delete();
    return response()->json([
      "success" => "Subscription Deleted successfully",
    ]);
  }
}
