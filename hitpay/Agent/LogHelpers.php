<?php

namespace HitPay\Agent;

use App\Business\Charge;
use App\Facades\Agent as AgentFacade;
use App\User;
use Exception;
use Illuminate\Database\Eloquent\JsonEncodingException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use phpDocumentor\Reflection\Types\This;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait LogHelpers
{
    /**
     * The agent instance.
     *
     * @var \HitPay\Agent\Agent|null
     */
    private $agentInstance;

    /**
     * Boot the log helpers trait for a model.
     */
    public static function bootLogHelpers() : void
    {
        static::creating(function (self $model) : void {
            $agentInstance = $model->getAgentInstance();

            $withExecutor = property_exists($model, 'logRequestWithExecutor') ? $model->logRequestWithExecutor : true;

            if ($withExecutor && $executor = $agentInstance->executor()) {
                $model->setAttribute('executor_id', $executor->getKey());
            }

            $model->setAttribute('request_ip_address', $agentInstance->ipAddress());
            $model->setAttribute('request_user_agent', $agentInstance->userAgent());
            $model->setAttribute('request_method', $agentInstance->method());
            $model->setAttribute('request_url', $agentInstance->url());

            if ($geolocation = $agentInstance->geolocation()) {
                $model->setAttribute('request_country', $geolocation->country_code);
            }

            $data = $model->getAttribute('request_data');

            if (is_null($data)) {
                $data = [];
            } elseif (!is_array($data)) {
                throw new Exception(sprintf('The `%s::$%s` is not null or array.', static::class, 'request_data'));
            }

            $request_data = array_merge($agentInstance->others($withExecutor), $data);
            if ($model instanceof Charge && isset($request_data['requests'])) {
                $request_data['requests'] = $model->removeUnusedData($request_data['requests']);
            }
            $model->setAttribute('request_data', $request_data);
        });
    }

    /**
     * Set agent instance.
     *
     * @param \HitPay\Agent\Agent $agentInstance
     *
     * @return $this
     */
    public function setAgentInstance(Agent $agentInstance) : self
    {
        $this->agentInstance = $agentInstance;

        return $this;
    }

    /**
     * Get agent instance.
     *
     * @return \HitPay\Agent\Agent
     */
    public function getAgentInstance() : Agent
    {
        if (!$this->agentInstance) {
            $this->agentInstance = AgentFacade::instance();
        }

        return $this->agentInstance;
    }

    /**
     * Set mutator for user-agent attribute.
     *
     * @param string|null $value
     */
    public function setRequestUserAgentAttribute(?string $value) : void
    {
        if (!is_null($value)) {
            $value = Str::limit($value, 1024, null);
        }

        $this->attributes['request_user_agent'] = $value ?: null;
    }

    /**
     * Set mutator for method attribute.
     *
     * @param string|null $value
     */
    public function setRequestMethodAttribute(?string $value) : void
    {
        if (!is_null($value)) {
            $value = strtolower($value);
        }

        $this->attributes['request_method'] = $value ?: null;
    }

    /**
     * Set mutator for URL attribute.
     *
     * @param string|null $value
     */
    public function setRequestUrlAttribute(?string $value) : void
    {
        if (!is_null($value)) {
            $value = Str::limit($value, 1024, null);
        }

        $this->attributes['request_url'] = $value ?: null;
    }

    /**
     * Set mutator for request data attribute.
     *
     * @param array|null $value
     */
    public function setRequestDataAttribute(?array $value) : void
    {
        if (!is_null($value)) {
            $value = $this->asJson($value);

            if ($value === false) {
                throw JsonEncodingException::forAttribute($this, 'request_data', json_last_error_msg());
            }
        }

        $this->attributes['request_data'] = $value;
    }

    /**
     * Get mutator for request data attribute.
     *
     * @param $value
     *
     * @return array|null
     */
    public function getRequestDataAttribute(?string $value) : ?array
    {
        return $this->fromJson($value);
    }

    /**
     * A helper to log attributes.
     *
     * @param array|null $attributes
     *
     * @return $this
     * @throws \Exception
     */
    public function logAttributes(?array $attributes) : self
    {
        if (!empty($attributes)) {
            if (property_exists($this, 'logRequestAttributesName')) {
                $attributesName = $this->logRequestAttributesName;
            } else {
                $attributesName = 'request_data';
            }

            $data = $this->getAttribute($attributesName);

            if (is_null($data)) {
                $data = [];
            } elseif (!is_array($data)) {
                throw new Exception(sprintf('The `%s::$%s` is not null or array.', static::class, $attributesName));
            }

            $data = array_merge($data, [
                'attributes' => $attributes,
            ]);

            $this->setAttribute($attributesName, $data);
        }

        return $this;
    }

    /**
     * Get the executor, probably is null too.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|null
     */
    public function executor() : ?BelongsTo
    {
        if (property_exists($this, 'logRequestWithExecutor') && $this->logRequestWithExecutor) {
            return $this->belongsTo(User::class, 'executor_id', 'id', 'executor');
        }

        return null;
    }
}
