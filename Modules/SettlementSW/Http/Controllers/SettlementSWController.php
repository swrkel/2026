<?php

namespace Modules\SettlementSW\Http\Controllers;

/**
 * Backward-compatible controller alias.
 *
 * Older sidebar links and Petro routes still reference SettlementSWController.
 * Keep those links working while routing every request through the single
 * authoritative SettlementSwDashboardController implementation.
 */
class SettlementSWController extends SettlementSwDashboardController
{
}
