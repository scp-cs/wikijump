<?php
declare(strict_types=1);

namespace Wikidot\Config;

class ForbiddenNames {

    public static array $sites = [
        '/^www[0-9]*$/',
        '/^[0-9]*www$/',
        '/^www\-/',
        '/^mail$/',
        '/^ftp$/',
        '/^http[s]?$/',
        '/^\-/',
        '/\-$/',
        '/^_/',
        '/_$/',
        '/^dev$/',
        '/^stage$/',
        '/^staging$/',
        '/^prod$/',
        '/^production$/',
        '/^official$/',
        '/^example/',
        '/^admin$/',
        '/^root$/',
        '/^error$/',
        '/^null$/',
        '/^undefined$/',
        '/^blog$/',
        '/^login/';
        '/^support$/',
        '/^helpdesk$/',
        '/wikidot/',
        '/wikijump/',
        '/^pro$/',
        '/^mail$/',
        '/^film$/',
        '/^porn/',
        '/^spam/',
        '/^web$/',
        '/^ssl$/',
        '/^tls$/',
        '/^payment[s]?$/',
        '/^pay$/',
        '/^secure$/',
        '/^service[s]?$/',
        '/^static[0-9]*/',
        '/^img$/',
        '/^image[s]?$/',
        '/^stat[s]?$/',
        '/^your\-?site$/',
        '/^template\-/',
        '/^your\-?(site|wiki)$/',
        '/^wdupload$/',
        '/^wjupload$/',
        '/^file[s]?$/',
        '/^upload[s]?$/',
        '/^api[0-9]*/',
    ];

    public static array $users = [
        '/^[0-9]*www[0-9]*$/',
        '/^mail$/',
        '/^\-/',
        '/\-$/',
        '/^dev$/',
        '/^blog$/',
        '/^login$/',
        '/^support$/',
        '/^helpdesk$/',
        '/wikidot/',
        '/wikijump/',
        '/^pro$/',
        '/^web$/',
        '/^ssl$/',
        '/^tls$/',
        '/^payment[s]?$/',
        '/^pay$/',
        '/^upload$/',
        '/^secure$/',
        '/^service[s]?$/',
        '/^api$/',
        '/^guru$/',
        '/^admin$/',
        '/^administrator/',
        '/^mod$/',
        '/^moderator/',
        '/^module/',
        '/^staff$/',
        '/^anon$/',
        '/^anonymous/',
        '/^example/',
        '/^unknown/',
        '/^guest/',
        '/^root$/',
        '/^error$/',
        '/^null$/',
        '/^undefined$/',
        '/^bot$/',
        '/^robot$/',
        '/^O5\-\w+$/',  // These are both SCP Wiki-related, but given how many users are registered on Wikidot
        '/^SCP\-\w+$/', // with these, I think it's a good idea to nip this in the bud at the account creation stage.
    ];

}
