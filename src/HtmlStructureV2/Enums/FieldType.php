<?php

namespace Sc\Util\HtmlStructureV2\Enums;

enum FieldType: string
{
    case TEXT = 'text';
    case PASSWORD = 'password';
    case ICON = 'icon';
    case TEXTAREA = 'textarea';
    case NUMBER = 'number';
    case SELECT = 'select';
    case RADIO = 'radio';
    case CHECKBOX = 'checkbox';
    case CASCADER = 'cascader';
    case DATE = 'date';
    case DATETIME = 'datetime';
    case DATE_RANGE = 'date_range';
    case UPLOAD = 'upload';
    case SWITCH = 'switch';
    case PICKER = 'picker';
    case HIDDEN = 'hidden';
}
