<?php

namespace Modules\PetroPD\Services;

enum ShiftState: string
{
    case Draft = 'draft';
    case PumperClosed = 'pumper_closed';
    case InSettlement = 'in_settlement';
    case Settled = 'settled';
    case Reopened = 'reopened';
}
