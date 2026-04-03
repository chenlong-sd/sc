<?php

namespace Sc\Util\HtmlStructureV2\Enums;

enum ActionIntent: string
{
    case CREATE = 'create';
    case EDIT = 'edit';
    case DELETE = 'delete';
    case REQUEST = 'request';
    case SUBMIT = 'submit';
    case CLOSE = 'close';
    case REFRESH = 'refresh';
    case CUSTOM = 'custom';
}
