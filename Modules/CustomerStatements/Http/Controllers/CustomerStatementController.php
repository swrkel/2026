<?php

namespace Modules\CustomerStatements\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\CustomerStatements\Services\CustomerStatementWorkspaceAdapter;

class CustomerStatementController extends Controller
{
    private CustomerStatementWorkspaceAdapter $workspace;

    public function __construct(CustomerStatementWorkspaceAdapter $workspace)
    {
        $this->workspace = $workspace;
    }

    /**
     * Open the complete Customer Statement workspace.
     *
     * The original CustomerStatements package contained only a placeholder
     * page.  The production statement engine already lives in the Customers
     * module, so this module uses a small adapter instead of duplicating the
     * statement, payment, logo, numbering and font-setting logic.
     */
    public function index(Request $request)
    {
        return $this->workspace->render($request);
    }
}
