<?php

namespace lanerp\common\Providers;

use Illuminate\Pagination\LengthAwarePaginator;

class PageService extends LengthAwarePaginator
{
    private string $primaryKey = "";

    /**
     * @return array
     * @usage
     */
    public function toArray()
    {
        $data  = [
            'data'     => $this->items->toArray(),
            'total'    => $this->total(),
            'pageSize' => (int)$this->perPage(),
            'page'     => $this->currentPage(),
        ];
        if ($this->primaryKey && ($model = data_get($this->items, 0)?->getModel()) && method_exists($model, "returnPermission")) {
            $data['primaryKey'] = $this->primaryKey;
            $data['permission'] = $model->returnPermission($this->primaryKey, $this->items);
        }
        return $data;
    }

    public function setPK($primaryKey): PageService
    {
        $this->primaryKey = $primaryKey;
        return $this;
    }
}
