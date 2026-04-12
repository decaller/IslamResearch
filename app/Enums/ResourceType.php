<?php

namespace App\Enums;

enum ResourceType: string
{
    case Quran = 'quran';
    case Hadith = 'hadith';
    case Tafsir = 'tafsir';
    case Syarh = 'syarh';
    case Language = 'language';
    case QuranicAction = 'quranic_action';
    case Other = 'other';
}
