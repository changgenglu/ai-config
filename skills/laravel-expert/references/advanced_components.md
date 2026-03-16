# Laravel 進階組件

## 4. Eloquent 進階模式

### 4.1 Query Scope

```php
// Local Scope
public function scopeActive(Builder $query): void
{
    $query->where('status', 'active');
}

public function scopeOfType(Builder $query, string $type): void
{
    $query->where('type', $type);
}

// 使用
User::active()->ofType('admin')->get();

// Global Scope（自動套用）
class ActiveScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $builder->where('deleted_at', null);
    }
}
```

### 4.2 Accessor & Mutator

```php
// Laravel 9+ 新語法
use Illuminate\Database\Eloquent\Casts\Attribute;

protected function fullName(): Attribute
{
    return Attribute::make(
        get: fn () => "{$this->first_name} {$this->last_name}",
    );
}

protected function password(): Attribute
{
    return Attribute::make(
        set: fn (string $value) => Hash::make($value),
    );
}
```

### 4.3 Custom Casts

```php
class MoneyCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): Money
    {
        return new Money($value, $attributes['currency'] ?? 'USD');
    }
    
    public function set(Model $model, string $key, mixed $value, array $attributes): int
    {
        return $value instanceof Money ? $value->cents : $value;
    }
}

// 使用
protected function casts(): array
{
    return [
        'price' => MoneyCast::class,
    ];
}
```

---

## 5. Event / Listener / Observer

### 5.1 Event 與 Listener

```php
// 定義 Event
class OrderPlaced
{
    public function __construct(
        public readonly Order $order,
    ) {}
}

// 定義 Listener
class SendOrderConfirmation
{
    public function handle(OrderPlaced $event): void
    {
        Mail::to($event->order->user)->send(
            new OrderConfirmationMail($event->order)
        );
    }
}

// 觸發
event(new OrderPlaced($order));
// 或
OrderPlaced::dispatch($order);
```

### 5.2 Observer

```php
class UserObserver
{
    public function created(User $user): void
    {
        Log::info("User created: {$user->id}");
    }
    
    public function updated(User $user): void
    {
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }
    }
    
    public function deleted(User $user): void
    {
        $user->orders()->delete();
    }
}

// 註冊（AppServiceProvider）
User::observe(UserObserver::class);
```

---

## 6. Form Request 驗證

### 6.1 基本用法

```php
class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Order::class);
    }
    
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
    
    public function messages(): array
    {
        return [
            'product_id.exists' => '商品不存在',
            'quantity.min' => '數量至少為 1',
        ];
    }
}
```

### 6.2 進階驗證

```php
public function rules(): array
{
    return [
        'email' => [
            'required',
            'email',
            Rule::unique('users')->ignore($this->user),
        ],
        'role' => [
            'required',
            Rule::in(['admin', 'user', 'guest']),
        ],
        'status' => [
            'required',
            Rule::enum(UserStatus::class),
        ],
    ];
}

// 條件驗證
public function withValidator(Validator $validator): void
{
    $validator->sometimes('phone', 'required', function ($input) {
        return $input->contact_method === 'phone';
    });
}
```

---

## 7. Policy / Gate 權限

### 7.1 Policy 定義

```php
class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        return $user->id === $order->user_id;
    }
    
    public function update(User $user, Order $order): bool
    {
        return $user->id === $order->user_id 
            && $order->status === 'pending';
    }
    
    public function delete(User $user, Order $order): bool
    {
        return $user->isAdmin();
    }
}

// 使用
$this->authorize('update', $order);
// 或
Gate::authorize('update', $order);
// 或
if ($user->can('update', $order)) { }
```

### 7.2 Gate 定義

```php
// AuthServiceProvider 或 AppServiceProvider
Gate::define('access-admin', function (User $user) {
    return $user->role === 'admin';
});

Gate::define('edit-settings', function (User $user) {
    return $user->isAdmin();
});

// 使用
if (Gate::allows('access-admin')) { }
@can('access-admin') ... @endcan
```

---

