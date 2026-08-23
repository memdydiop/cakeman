<div class="space-y-6">
    <x-page-header title="Recettes" subtitle="BOM — coût de revient auto" :breadcrumbs="[['label' => 'Recettes']]" />

    @if (!tenant()?->hasFeature('recipes.enabled'))
        <flux:callout variant="warning" icon="lock-closed">Recettes non incluses dans votre plan.</flux:callout>
    @else
        <flux:card>
            <x-card-header icon="book-open" title="Recettes" subtitle="Une recette par produit" :actions="[
                ['label' => 'Nouvelle recette', 'icon' => 'plus', 'x-on:click' => '$flux.modal(\'create-recipe\').show()'],
            ]" />
            <x-card-body>
                <div class="space-y-4">
                    @forelse ($this->recipes as $recipe)
                        <div class="rounded-lg border border-zinc-200 p-4 {{ $selectedRecipeId === $recipe->id ? 'ring-1 ring-primary' : '' }}">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="font-medium">{{ $recipe->product->name }}</div>
                                    <div class="text-xs text-zinc-500">Rendement {{ $recipe->yield_quantity }} · Coût {{ number_format($recipe->cost(), 0, ',', ' ') }} FCFA</div>
                                </div>
                                <div class="flex gap-1">
                                    <flux:button size="xs" variant="subtle" wire:click="selectRecipe({{ $recipe->id }})">Gérer</flux:button>
                                    <flux:button size="xs" variant="subtle" icon="trash" wire:click="delete({{ $recipe->id }})" wire:confirm="Supprimer ?" />
                                </div>
                            </div>

                            @if ($selectedRecipeId === $recipe->id)
                                <div class="mt-4 border-t pt-4">
                                    <div class="flex gap-2 mb-3">
                                        <flux:select wire:model="ingredient_id" placeholder="Ingrédient" class="w-48">
                                            @foreach ($this->ingredientOptions as $ing)
                                                <flux:select.option :value="$ing->id">{{ $ing->name }} ({{ $ing->stock_quantity }}{{ $ing->unit }})</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                        <flux:input type="number" step="0.01" wire:model="quantity" class="w-24" />
                                        <flux:button size="sm" variant="primary" wire:click="addIngredient">Ajouter</flux:button>
                                    </div>
                                    <ul class="space-y-1">
                                        @foreach ($recipe->ingredients as $ri)
                                            <li class="flex justify-between text-sm">
                                                <span>{{ $ri->ingredient->name }} — {{ $ri->quantity }}{{ $ri->ingredient->unit }} ({{ number_format((float) $ri->quantity * $ri->ingredient->unit_cost, 0, ',', ' ') }} FCFA)</span>
                                                <flux:button size="xs" variant="ghost" icon="x-mark" wire:click="removeIngredient({{ $ri->id }})" />
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    @empty
                        <flux:text class="text-sm text-zinc-500">Aucune recette.</flux:text>
                    @endforelse
                </div>
            </x-card-body>
        </flux:card>

        <flux:modal name="create-recipe" variant="flyout">
            <form wire:submit="create">
                <div class="space-y-6">
                    <div>
                        <flux:heading size="lg">Nouvelle recette</flux:heading>
                    </div>
                    <flux:select label="Produit" wire:model="product_id" placeholder="Choisir">
                        @foreach ($this->productsWithoutRecipe as $p)
                            <flux:select.option :value="$p->id">{{ $p->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:input type="number" label="Rendement" wire:model="yield_quantity" />
                    <flux:textarea label="Instructions" wire:model="instructions" rows="3" />
                    <div class="flex justify-end gap-2">
                        <flux:button variant="ghost" x-on:click="$flux.modal('create-recipe').close()">Annuler</flux:button>
                        <flux:button variant="primary" type="submit">Créer</flux:button>
                    </div>
                </div>
            </form>
        </flux:modal>
    @endif
</div>
