<?php

namespace App\Enums;

enum EntityType: string
{
    case Person = 'person';
    case Location = 'location';
    case Event = 'event';
    case Concept = 'concept';
    case Book = 'book';
}
