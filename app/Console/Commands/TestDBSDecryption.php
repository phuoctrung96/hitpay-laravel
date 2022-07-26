<?php

namespace App\Console\Commands;

use Crypt_GPG;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Throwable;

class TestDBSDecryption extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hitpay:test-dbs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test whether the server can decrypt encrypted message from DBS.';

    protected $stagingData = '-----BEGIN PGP MESSAGE-----
Version: BCPG v1.60

hQGMA2X/Nt20IJFFAQwAlGhCqyF54m1VOWbnDmS+vwi98VGHd6AIGb/oqRS00VO2
+ESF3PuZGQ8/rl2MEUKb6tyMTsWUd3hUxxrdjp4PpAmPQ7MDKSKo5BJF/aPUmqk4
t5BYG6xOLiD47+LvVeRABpYjWrSYI1yyOY6Jdv+mfB6SqNRA2Fmj640dSw7DC/Cg
VSE6kNK0NHBIk7ra9qsKLV1cc11FTMYjli/xapCl9SaF6xnwEBbSzcqvIWFNHWzZ
NQkpNdSBoj+sICp4uhidos+yzMKol5vgggf+5SpnJ2cUfqMguZtm8IRfWLc9SjQB
pgAb1WuOpCotGs8IYtHhiIy5eF2x0YFoJKt4gkCFbk4bEMQsECaTtoE0VDAOgjrU
q3I9LBa96f3ECSKC9/bv5mmjqLmrH45B8sMDt/6kWs1yJAno5j0KsKtuU0JeRHVq
55GvTZ323i4amuuJXmRHj8HeB5YPdiquxjm0QRZkTKgX9Pceyd9onGNBD3bQVHD5
dHVPOvcBUvaqr3SxGDlZ0sJ1AWAUxal15R2WGkS60I4SwmJQHeYcHALOY7x/c8zU
WgWcuF6kOYbkRCZrVF+kTbVcXx7hJ25MKdxIf0dsA0u/XQ25GMIjtJql+uLnw7yN
P5DjsoZuLpUu6Hc9TA4TZwiWrMUS1ywk409uSXe+hZDSlK2xIcC2rbY52SGDmDQ5
N0M4CsLE0b55DLHGhGfUQzbdtHrpabgy5WIm2WIBHb1qGwhqYOVhWoOx3H6Ilj0s
rH48U10IIaKQriWSAzRqsGny0uYJEp3Vwr2+IgxMQWsf19DOGgyLjIn/XDSDww3b
rxMHMlHiEC9aiTLgMUnm5J+gzgDh+LKAogo81TOSZ/J0qrqxWLXfXY3VqvQlX1xF
W3Z0qj1WZ5YGYFSHpSQ5xC6du307nHIQ6L7WBB76KtH0niT/reaD0v/HDCYxzShQ
b2Ve+rkLS6UTQV0//hTGgWjXfmDE7NFNnlCs3/T5rUJq6ujm8y9vDCajYzVTx37A
wlmpTx+RgUYzXs0UX2s33ByZqGKre4pTY2X1M3tmT0Lq3QidaUyQOkfMVA5T+fTX
jcrAqIyXIfKjjL0f5dG9d9mAkmp7Y7lClmiPYryv5ZEIk0XrnTmZXyhgkS80mmU3
L7Ik+ZXDsz7nh0d3GKpK/OlIxdjkudcfNhqj90jY28D38yuNkrWXuXDlwCt4QEe+
DFYIpwD8/r2idsEHeaUdNevrFMMZw0FXm0TW2GGwDdJKjr9OvW8a4HJ2tvNQ+Ho+
sYBvO3OX5m6h5hR/My+ZBY1XQ3/WHV46TY5F/yF38TAW47a9IvdG0jlQXJnugdBX
gwQuH+yv8T9iKHBB2pATvieCYX2+b6JmeA/Ih+imJEimKVC0lyhj1t3fjI8n6EMG
MXQ3hdj90c5hqE8z2YuXMpuBCTEkW3o1CDDzucOQzygtkQawHpQNwKsXraiSobT4
3EWfrRfEgS/uiYjBXW5FotgpRcasHC4zzqUAQapwu1g3irzIE9LKKECqnzFUYnyO
D+wnjrWpqF6UFT8tkJQu1cNuGdGhpLpzDHGN9R1pHCsF2tZJWtn1H6XXenZZ8z5+
DGTGEP9vt0YXSpsTowyHRN26wuKwMc4=
=s/oK
-----END PGP MESSAGE-----
';

    protected $productionData = '-----BEGIN PGP MESSAGE-----
Version: BCPG v1.60

hQGMA2HHzsogddyiAQv+LGWBeiHrIkQWTGw40yFkZYxOWzG/BBNRKEs0tpXN3XyE
mpmuyg9JCT7lht2x9ex8xX59TBnayqeJjfW0ubZlWDOONKdFQlGjp+yPdIW0oBFH
HkRAd/iHQj9TbbEnjwSVennOikWPraU3Y6BMSJZJ1trviDFvGoXPFTji7so1MF3o
g8W4DexkV6l6XZYZoMdwVI8Xy2v57axF5DACZJIuZqVDCjVhOPTOxtNq34XA+cwx
WBonBg5X8vt7O/STZ+8vUqycmCyT0pbD2Kx1WxNFIPnRHg1a8JQiLP5LFBxW1beW
gS1rOW1kWnKJhCpFAlZtolYRbsodShYIDxGig2WvrAFE+IXA2q2Om2N5cyf4k9nb
w/ZY7kU0QDrWhf/X1OXOnXP7uTaQsFuHA5wfi/lQGRMvPOWNnPLAleGf3OjO+VS0
pktenxiGrtggWdVQU500SKXHMwCaDrvPbKC7aIba0dklu1x4zKPH5uPPWeMO76XT
4hWHbH02BMcGzD0MluHK0sJ3AYE7hJQ8zIwipqlP3hGKfkaR3JIwM2yj3+aYpfBF
/xnejFWFzTlOFZsxBg63AZEIyXCFE/ja1WLIJZM+/LAxvOuRZ5Hzy6bXRvatlqVf
R47sSYl8sIQEov6SXf6uBNajm5m/hMMOf4IPJET/wjlT7lHC6kry1xOMta1rKZwX
8xP7wZA5zRjJG/MSNvVufAsQBETi3xSRsQdOHcy32+QrdAUO4aiDYSznJww7qxOq
IeROTXK+I1rjLinVMh4CfNN03tPUAGrFIsOvoFrBYNm1gjQy7OywDri019vTSz1u
VOn5G1/z7WCsnc4xCgveLwVkwZauRAOAmY0GkTLRtQXE0yOr/VS4PcqZQe9MxSTs
sorbFJ7vUZEMQ+VFnwXfo+kpYYijyPjHjLjN9iljUqBQ98ZY98TugTSzJbZKKx7i
quzy3X+KBOUnLSM34LLoYEnnduWxaM9jXvXkRFGDimSH9jhODLVVDGzqwW6dUDWh
7yiEA5Zw2lTulcgtSHVejTplY0NTAdqGup5uvPhZh01RTTAFjUbrVTWPLa3IOuTZ
rpbmEbll2GegM7IG48v6eZUUbtVYJaCnEEtrTeakEdgBpKDj4pAnLo2Co+ziEr1J
miBaUlVToz4o6cXdryXGuSlsR8/5nmLSzvYd2G0LBhKnQOgF+8vhdqrhL7I/Iibc
N47oop6bbVic1IcGLn0+vt67hAp/MvcAkyyOHNQhukLa8GCp+lfDkhOT+huR7q9a
gtBP9HQedYSTxtdisekvddeOf21eMkPmEGQhvYWRVM1XGF+c1oiRE8BQXdVWv0Ku
pk8/gwS/S2CH+gRS4/K38triS4eRp8/xwZPunuIP73BgNR0geUkxDXMn1BfihKpU
GZxZtSi6Mxy3eaiQ6DHZyACTcSh45Va8U+Y1VYZzwAKJkBox2qavsAtVpuY2mwxw
d8kW+0/YXtQncmQhgTFT/13uOKsCvn3TDm+X7jmyUKwWDvmO92J149kpHvaToTHP
pLrTZqyBqKlEr38uysSyP2ud96C7tryn+SBsIe8cL87pq+j7txGHZX5xsve8QZvI
rlAGKTro7PxLMoQr8h24CEuhu5NszS2zig==
=zuDz
-----END PGP MESSAGE-----
';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() : int
    {
        $data = App::environment('production') ? $this->productionData : $this->stagingData;

        try {
            $gpg = new Crypt_GPG([
                'homedir' => '/home/ubuntu/.gnupg',
                'digest-algo' => 'SHA256',
                'cipher-algo' => 'AES256',
                'compress-algo' => 'zip',
                'debug' => Config::get('app.debug'),
            ]);

            $signKey = $gpg->importKey(file_get_contents(storage_path('dbs.key')));
            $gpg->addSignKey($signKey['fingerprint']);

            $decrypt = $gpg->importKey(file_get_contents(storage_path('private.key')));
            $gpg->addDecryptKey($decrypt['fingerprint']);

            $content = $gpg->decryptAndVerify($data);
            $content = json_decode($content['data'], true);

            $this->info(json_encode($content, JSON_PRETTY_PRINT));
        } catch (Throwable $exception) {
            $this->error('Decryption failed, error message: '.$exception->getMessage());
        }

        return 0;
    }
}
