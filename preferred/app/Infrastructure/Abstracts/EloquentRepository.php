<?php

namespace Preferred\Infrastructure\Abstracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Cache;
use Preferred\Infrastructure\Contracts\BaseRepository;
use Ramsey\Uuid\Uuid;

abstract class EloquentRepository implements BaseRepository
{
    /**
     * @var Model
     */
    protected $model;

    /**
     * @var bool
     */
    protected $withoutGlobalScopes = false;

    /**
     * @var array
     */
    protected $with = [];

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * {@inheritdoc}
     */
    public function with(array $with = []): BaseRepository
    {
        $this->with = $with;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function withoutGlobalScopes(): BaseRepository
    {
        $this->withoutGlobalScopes = true;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function store(array $data): Model
    {
        return $this->model->with([])->create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function update(Model $model, array $data): Model
    {
        return tap($model)->update($data);
    }

    /**
     * {@inheritdoc}
     */
    public function findByFilters(): LengthAwarePaginator
    {
        return $this->model->with($this->with)->paginate();
    }

    /**
     * {@inheritdoc}
     */
    public function findOneById(string $id): Model
    {
        if (!Uuid::isValid($id)) {
            throw (new ModelNotFoundException())->setModel(get_class($this->model));
        }

        if (!empty($this->with) || $this->authCheck()) {
            return $this->findOneBy(['id' => $id]);
        }

        return Cache::remember($id, 3600, function () use ($id) {
            return $this->findOneBy(['id' => $id]);
        });
    }

    private function authCheck()
    {
        return auth()->check() && config('auth.defaults.guard') === 'spa';
    }

    /**
     * {@inheritdoc}
     */
    public function findOneBy(array $criteria): Model
    {
        if (!$this->withoutGlobalScopes) {
            return $this->model->with($this->with)
                ->where($criteria)
                ->orderBy('created_at', 'desc')
                ->firstOrFail();
        }

        return $this->model->with($this->with)
            ->withoutGlobalScopes()
            ->where($criteria)
            ->orderBy('created_at', 'desc')
            ->firstOrFail();
    }
}
