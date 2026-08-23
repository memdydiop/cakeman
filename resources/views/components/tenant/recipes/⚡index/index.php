<?php

use App\Models\Ingredient;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Recettes')] class extends Component
{
    public ?int $product_id = null;

    public int $yield_quantity = 1;

    public string $instructions = '';

    public ?int $selectedRecipeId = null;

    public ?int $ingredient_id = null;

    public float $quantity = 1;

    #[Computed]
    public function recipes()
    {
        return Recipe::with(['product', 'ingredients.ingredient'])->get();
    }

    #[Computed]
    public function productsWithoutRecipe()
    {
        $used = Recipe::pluck('product_id')->all();

        return Product::whereNotIn('id', $used)->where('is_active', true)->orderBy('name')->get();
    }

    #[Computed]
    public function ingredientOptions()
    {
        return Ingredient::orderBy('name')->get();
    }

    public function create(): void
    {
        abort_if(! tenant()?->hasFeature('recipes.enabled'), 403, 'Recettes non incluses dans votre plan.');

        $validated = $this->validate([
            'product_id' => ['required', 'exists:products,id', 'unique:recipes,product_id'],
            'yield_quantity' => ['required', 'integer', 'min:1'],
            'instructions' => ['nullable', 'string'],
        ]);

        Recipe::create($validated);
        $this->reset(['product_id', 'yield_quantity', 'instructions']);
        $this->yield_quantity = 1;
        Flux::modal('create-recipe')->close();
        Flux::toast(__('Recette créée.'));
    }

    public function selectRecipe(int $id): void
    {
        $this->selectedRecipeId = $id;
    }

    public function addIngredient(): void
    {
        $this->validate(['selectedRecipeId' => ['required', 'exists:recipes,id'], 'ingredient_id' => ['required', 'exists:ingredients,id'], 'quantity' => ['required', 'numeric', 'min:0.01']]);

        RecipeIngredient::updateOrCreate(
            ['recipe_id' => $this->selectedRecipeId, 'ingredient_id' => $this->ingredient_id],
            ['quantity' => $this->quantity],
        );

        $this->reset(['ingredient_id', 'quantity']);
        $this->quantity = 1;
        Flux::toast(__('Ingrédient ajouté.'));
    }

    public function removeIngredient(int $id): void
    {
        RecipeIngredient::findOrFail($id)->delete();
        Flux::toast(__('Retiré.'));
    }

    public function delete(int $id): void
    {
        Recipe::findOrFail($id)->delete();
        Flux::toast(__('Recette supprimée.'));
    }
};
