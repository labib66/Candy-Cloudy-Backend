<?php

namespace Common\Csv;

use Illuminate\Support\Facades\Auth;
use Common\Auth\Jobs\ExportRolesCsv;
use Common\Auth\Jobs\ExportUsersCsv;

class CommonCsvExportController extends BaseCsvExportController
{
    public function exportUsers()
    {
        return $this->exportUsing(new ExportUsersCsv(Auth::guard('api')->id()));
    }

    public function exportRoles()
    {
        return $this->exportUsing(new ExportRolesCsv(Auth::guard('api')->id()));
    }
}
