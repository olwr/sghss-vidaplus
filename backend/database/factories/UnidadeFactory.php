<?php

namespace Database\Factories;

use App\Domains\Unidades\Models\Unidade;
use App\Shared\Enums\StatusUnidade;
use App\Shared\Enums\TipoUnidade;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Unidade> */
class UnidadeFactory extends Factory
{
    protected $model = Unidade::class;

    public function definition(): array
    {
        return [
            'tipo' => fake()->randomElement(TipoUnidade::cases()),
            'nome' => 'Unidade '.fake()->city().' '.Str::upper(Str::random(4)),
            'cnpj' => fake()->unique()->cnpj(false),
            'status' => StatusUnidade::Ativa,
            'telefone' => fake()->numerify('11#########'),
            'email' => fake()->unique()->safeEmail(),
            'cep' => fake()->numerify('########'),
            'logradouro' => fake()->streetName(),
            'numero' => (string) fake()->buildingNumber(),
            'bairro' => fake()->citySuffix(),
            'cidade' => fake()->city(),
            'uf' => fake()->stateAbbr(),
        ];
    }

    public function tipo(TipoUnidade $tipo): static
    {
        return $this->state(['tipo' => $tipo]);
    }

    public function desativada(): static
    {
        return $this->state(['status' => StatusUnidade::Desativada]);
    }
}
