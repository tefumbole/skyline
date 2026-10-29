<?php

namespace App\Support;

class TestingManual
{
    public static function sections()
    {
        return [
            self::section('public', 'Public website', 'Open the site in a private window so you see what a guest sees.', [
                self::item('pub-nav', 'The menu is the public menu', [
                    'Open the home page.',
                    'Read the links across the top.',
                ], 'The links are Home, Services, About, Gallery, Events, Reviews, and Admin login.', 'dates'),
                self::item('pub-services', 'Services shows the three parent counters', [
                    'Open Services.',
                ], 'Only three cards appear: Chill on the Hill, Restaurant, and Bar. Each card uses that category’s own photo.', 'services'),
                self::item('pub-gallery', 'Gallery photos open quickly', [
                    'Open Gallery.',
                    'Click one photo so the larger image opens.',
                ], 'The grid loads without a long wait, and the larger image is the same photo.', null),
            ]),
            self::section('dates', 'Choose a night', 'These checks are on Chill on the Hill, before a table is chosen.', [
                self::item('date-nights', 'Eight nights, and not 28 or 29 December', [
                    'Open Chill on the Hill.',
                    'Count the date cards.',
                ], 'The nights are 24, 25, 26, 27, 30 and 31 December 2026, then 1 and 2 January 2027. The 28th and 29th are not listed.', 'dates'),
                self::item('date-titles', 'Each night has its title', [
                    'Read the gold line under each date.',
                ], 'The titles are Locked in, Get detty, Old school takeover, Roots & Rythms Cultural night, Countdown, All white party, Karaoke night, and Finale.', 'dates'),
                self::item('date-discount', 'The old discount is gone', [
                    'Look at every date card.',
                ], 'No card says 20% off.', null),
            ]),
            self::section('experience', 'Choose a location', 'Pick any night, then stay on the experience page.', [
                self::item('exp-cards', 'Four location cards fit the page', [
                    'Select Friday 25 December.',
                    'Stay on Choose your experience.',
                ], 'Four cards show a photo, the location name, how many tables remain, and View tables. On a laptop you should not have to scroll to see them.', 'experience'),
                self::item('exp-fold', 'The round icon collapses a card', [
                    'Hover the gold circle on a card.',
                    'Click it once, then click it again.',
                ], 'The circle fills gold on hover. The first click hides that card’s photo. The second click shows it again. The other cards stay open.', null),
                self::item('exp-name', 'The card name matches Locations', [
                    'Note a name, for example the first card.',
                    'Compare it with Admin, COTH Reservations, Locations.',
                ], 'The guest sees the name saved on the Locations tab. After a rename, a refresh shows the new name.', 'experience'),
            ]),
            self::section('tables', 'Choose a table', 'Open one location, such as Gold — Hall.', [
                self::item('tbl-remaining', 'Only remaining tables are counted', [
                    'Open a location.',
                    'Read a group such as Hall-T10.',
                ], 'The card says how many tables remain. It does not say how many are taken.', 'tables'),
                self::item('tbl-colors', 'Opening a group shows both colours', [
                    'Click a group so it opens.',
                ], 'The gray table and the teal table are shown large, each with its colour name and Reserve this table.', null),
                self::item('tbl-code', 'The code starts with the location code', [
                    'Read a table code, such as HALL-T10-01.',
                ], 'The letters before the first dash are that location’s code. If the location code was changed, these letters match the new code.', 'tables'),
            ]),
            self::section('reserve', 'Reserve and pay', 'Open Reserve this table on a Hall table for 10. Do not submit a real payment unless you mean to.', [
                self::item('res-chairs', 'Extra chairs are offered first', [
                    'Open the reserve page.',
                    'Use the plus button once.',
                ], 'Add extra chairs sits above the phone number. A Hall table for 10 includes 10 seats and allows up to 4 extra chairs at 50,000 FCFA each.', 'reserve'),
                self::item('res-deposit', 'The deposit includes the extra chairs', [
                    'Note the deposit with 0 extra chairs.',
                    'Add one chair and read the deposit again.',
                ], 'The deposit is 20% of the table plus the chairs. One extra chair on an 800,000 table raises the total to 850,000 and the deposit to 170,000.', null),
                self::item('res-rates', 'Extra-chair prices follow the table', [
                    'Open a 6-seat table, an Outer Restaurant table that is not 6 seats, and a Yard, Hall, or Pool table that is not 6 seats.',
                ], 'Any 6-seat table is 75,000 FCFA per extra chair. Outer Restaurant is 100,000. Yard, Hall, and Pool are 50,000. A rate saved on that table in admin replaces these.', null),
                self::item('res-phone', 'The country and the number are separate', [
                    'Look at the WhatsApp field.',
                ], 'Cameroon is +237 in its own box. The number box does not ask you to type the country code again.', 'reserve'),
            ]),
            self::section('desk', 'Admin table desk', 'Sign in as admin or owner. Open COTH Reservations.', [
                self::item('adm-tables', 'Tables is the first tab', [
                    'Open COTH Reservations.',
                ], 'The page opens on Tables. Each table is a white card with its photo, name, price, seats, style, and colour.', 'admin-tables'),
                self::item('adm-filter', 'Location chips filter the cards', [
                    'Click one location chip, then All sections.',
                ], 'Only that location’s tables stay on screen. All sections brings the rest back.', null),
                self::item('adm-save', 'Saving stays on Tables', [
                    'Change a table name and save it.',
                    'Change it back if this was only a test.',
                ], 'A green success line appears and the Tables tab is still selected.', 'admin-tables'),
                self::item('adm-look', 'The desk matches the admin colours', [
                    'Compare the desk with another admin page.',
                ], 'The page is white, headings and buttons are navy, and the selected tab is gold.', 'admin-tables'),
            ]),
            self::section('tabs', 'The other admin tabs', 'Stay on COTH Reservations and open each tab.', [
                self::item('tab-res', 'Reservations can be filtered', [
                    'Open Reservations.',
                    'Filter by a date or a customer and apply it.',
                ], 'The list narrows, and you remain on Reservations.', null),
                self::item('tab-avail', 'One night can be closed', [
                    'Open Availability.',
                    'Pick a night and read a table’s status.',
                ], 'An open table can be disabled for that night. A booked table stays booked.', null),
                self::item('tab-chairs', 'Extra chairs can be capped', [
                    'Open Extra chairs.',
                ], 'Each night has a total. Leave it blank for no cap. Held and booked counts are shown beside it.', null),
                self::item('tab-rules', 'Rules do not rewrite old bookings', [
                    'Open Rules and read the deposit percent.',
                ], 'The deposit percent is there. The page says existing reservations keep the price they were given.', null),
                self::item('tab-audit', 'Audit records the change', [
                    'Open Audit after you have saved a table or a location.',
                ], 'The latest line names what changed.', null),
                self::item('tab-order', 'Locations is the last tab', [
                    'Read the tab row from left to right.',
                ], 'The order is Tables, Reservations, Availability, Extra chairs, Rules, Audit, then Locations.', null),
            ]),
            self::section('locations', 'Locations', 'Use a spare spelling you can change back, so guests are not left on a test name.', [
                self::item('loc-save', 'A location rename updates its tables', [
                    'Open Locations.',
                    'Change the name guests see, and change the table code.',
                    'Save that location.',
                ], 'The success line names the location and how many tables changed code. Example: tables changed from YARD to GARDEN.', 'locations'),
                self::item('loc-guest', 'Guests see the new name and the new codes', [
                    'Refresh the guest experience page for a night.',
                    'Open that location and read a table code.',
                ], 'The card uses the new name. Every table that used the old code now starts with the new code. Reservations already made stay on the same tables.', null),
                self::item('loc-clash', 'Two locations cannot share one code', [
                    'Set a location’s code to a code another location already uses, such as HALL.',
                    'Save.',
                ], 'The page refuses the save and the other location’s tables are unchanged. Put the test code back afterwards.', null),
            ]),
            self::section('products', 'Products and People', 'These checks cover the menus beside Chill on the Hill.', [
                self::item('prod-board', 'Chill on the Hill in Products opens by location', [
                    'Open Product.',
                    'Choose the Chill on the Hill category.',
                    'Open one location.',
                ], 'You see the locations first. Opening one lists the tables in it.', null),
                self::item('prod-edit', 'Names and colours are edited there, prices on the desk', [
                    'On a table, change the name or the colour template and save.',
                    'Look for a price box on that same screen.',
                ], 'The name and template save. The price is not edited on this screen. Price is on COTH Reservations, on the Tables tab.', null),
                self::item('people-lists', 'People includes suppliers', [
                    'Open the People menu.',
                ], 'The lists include Customer List, User List, Biller List, and Supplier List.', null),
            ]),
        ];
    }

    public static function keys()
    {
        $keys = [];
        foreach (self::sections() as $section) {
            foreach ($section['items'] as $item) {
                $keys[] = $item['key'];
            }
        }

        return $keys;
    }

    public static function has($key)
    {
        return in_array((string) $key, self::keys(), true);
    }

    protected static function section($id, $title, $intro, array $items)
    {
        return [
            'id' => $id,
            'title' => $title,
            'intro' => $intro,
            'items' => $items,
        ];
    }

    protected static function item($key, $title, array $steps, $expect, $shot)
    {
        return [
            'key' => $key,
            'title' => $title,
            'steps' => $steps,
            'expect' => $expect,
            'shot' => $shot,
        ];
    }
}
