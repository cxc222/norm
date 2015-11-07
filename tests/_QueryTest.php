<?php

use Norm\Database;


abstract class _QueryTest extends PHPUnit_Framework_TestCase
{
    protected $db;

    function testBasic()
    {
        $this->assertEquals(0, 
            $this->db->with('user')->count()
        );
        $this->assertEquals(1,
            $this->db->with('user')->insert([
                'name' => 'jack',
                'dob' => '2015-11-08 00:00:00',
            ])
        );
        $this->assertEquals(2,
            $this->db->with('user')->insert([
                'name' => 'rose',
            ])
        );
        $this->assertEquals($this->db->with('user')
                    ->where('id', '<', 2)
                    ->where('id', 1)
                    ->value('name'), 
                'jack');
        // transaction
        $self = $this;
        $db = $this->db;
        $db->atomic(function() use ($db, $self) {
            $db->with('user')->where('id', 2)->delete();
            $self->assertEquals($db->with('user')->count(), 1);
            return FALSE;
        });
        $this->assertEquals($db->with('user')->count(), 2);
    }

    function testJoin()
    {
        $this->db->with('user')->insert([
            'id' => 1,
            'name' => 'jack',
        ]);
        $this->db->with('balance')->insert([
            'user_id' => 1,
            'amount' => 100,
        ]);
        $row = $this->db->with('user u')
            ->join('balance b', 'b.user_id = u.id')
            ->where('u.id', 1)
            ->get('u.id, u.name, b.amount');
        $this->assertEquals($row['amount'], 100);
        $this->assertEquals($row['name'], 'jack');
    }
}

