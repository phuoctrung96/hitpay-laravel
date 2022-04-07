<?php

namespace HitPay\Stripe\CustomAccount\Filelink;

class Create extends Filelink
{
    /**
     * @param $resultId
     * @return FileLink
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function handle($resultId)
    {
        $this->getCustomAccount();

        return \Stripe\FileLink::create(
            ['file' => $resultId],
            ['stripe_version' => $this->stripeVersion]
        );
    }
}
