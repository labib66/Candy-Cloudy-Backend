<?php

namespace Common\Workspaces\Controllers;

use Illuminate\Support\Facades\Auth;
use Common\Core\BaseController;
use Common\Database\Datasource\Datasource;
use Common\Workspaces\Actions\CrupdateWorkspace;
use Common\Workspaces\Actions\DeleteWorkspaces;
use Common\Workspaces\Requests\CrupdateWorkspaceRequest;
use Common\Workspaces\Workspace;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;

class WorkspaceController extends BaseController
{
    public function __construct(
        protected Workspace $workspace,
        protected Request $request
    ) {
    }

    public function index()
    {
        $userId = $this->request->get('userId');

        $builder = $this->workspace
            ->newQuery()
            ->withCount(['members'])
            ->with([
                'members' => function (HasMany $builder) {
                    $builder->with('permissions')->currentUserAndOwnerOnly();
                },
            ]);
        if ($userId) {
            $builder->forUser($userId);
        }

        $dataSource = new Datasource($builder, $this->request->all());

        $pagination = $dataSource->paginate();

        $pagination->transform(function (Workspace $workspace) {
            return $workspace->setCurrentUserAndOwner();
        });

        return $this->success(['pagination' => $pagination]);
    }

    public function show(Workspace $workspace)
    {
        $workspace->load(['invites', 'members']);

        if (
            $workspace->currentUser = $workspace->members
                ->where('id', Auth::guard('api')->id())
                ->first()
        ) {
            $workspace->currentUser->load('permissions');
        }
        return $this->success(['workspace' => $workspace]);
    }

    public function store(CrupdateWorkspaceRequest $request)
    {
        $workspace = app(CrupdateWorkspace::class)->execute($request->all());
        $workspace->loadCount('members');
        $workspace
            ->load([
                'members' => function (HasMany $builder) {
                    $builder->currentUserAndOwnerOnly();
                },
            ])
            ->setCurrentUserAndOwner();

        return $this->success(['workspace' => $workspace]);
    }

    public function update(
        Workspace $workspace,
        CrupdateWorkspaceRequest $request
    ) {
        $workspace = app(CrupdateWorkspace::class)->execute(
            $request->all(),
            $workspace,
        );

        return $this->success(['workspace' => $workspace]);
    }

    public function destroy(string $ids)
    {
        $workspaceIds = explode(',', $ids);

        app(DeleteWorkspaces::class)->execute($workspaceIds);

        return $this->success();
    }
}
