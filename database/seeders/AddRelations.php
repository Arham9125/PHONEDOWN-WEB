<?php

namespace Database\Seeders;

use App\Models\Relationship;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AddRelations extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $array = ["Guardian","Husband","Wife","Daughter","Son"];

        Relationship::truncate();

        foreach ($array as $key => $value) {
            $data = new Relationship();
            $data->id = $key + 1;
            $data->relation = $value;
            $data->save();
        }
    }
}
