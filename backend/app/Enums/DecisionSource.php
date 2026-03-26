<?php

namespace App\Enums;

enum DecisionSource: string
{
    case Llm = 'llm';
    case Fallback = 'fallback';
    case Manual = 'manual';
}
