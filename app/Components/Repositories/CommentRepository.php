<?php


namespace App\Components\Repositories;


use MongoDB\Client;

class CommentRepository
{
    protected $client;

    protected $db = 'qyw';

    protected $collection = 'comments';

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function get()
    {
        // todo
    }

    public function set($from, $to, $content)
    {
        $this->store(['from' => $from, 'to' => $to, 'content' => $content]);
    }

    public function has()
    {
        // todo
    }

    public function remove()
    {
        // todo
    }

    protected function store($data)
    {
        $default = [
            'created_at' => date('Y-m-d H:i:s'),
            'read_at' => null
        ];
        $data = array_merge($default, $data);
        $this->client->{$this->db}->{$this->getCollection()}->insertOne($data);
    }

    /**
     * @return string
     */
    public function getCollection(): string
    {
        return $this->collection;
    }

    /**
     * @param string $collection
     */
    public function setCollection(string $collection)
    {
        $this->collection = $collection;
    }
}