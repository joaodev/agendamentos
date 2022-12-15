<?php 

namespace App\Interfaces;

interface CrudInterface
{
	public function createAction(): void;
	public function readAction(): void;
	public function updateAction(): void;
	public function deleteAction(): bool;
}