<?php

namespace App;

class EnumClass
{
    public static string $USER_HEADER = 'x-app-haumichtot';
    public static int $ADMIN_ROLE = 0;
    public static int $LEHRER_ROLE = 1;
    public static int $BETREUER_ROLE = 2;
    public static int $WERKSTATT_ROLE = 3;
    public static int $SIMPLE_USER = 4;

    public static string $STATE_OPEN ='offen';
    public static string $STATE_VERIFIED ='verifiziert';
    public static string $STATE_WIP ='in bearbeitung';
    public static string $STATE_CLOSED ='geschlossen';

    public static string $APP_SECRET = 'o8edrp98u45fk8uso3489m5dsp85zeu60dp9i4sr';
}