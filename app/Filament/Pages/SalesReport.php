<?php
namespace App\Filament\Pages;
use App\Models\CouponRedemption; use App\Models\Inventory; use App\Models\Order; use App\Models\OrderItem; use App\Models\Payment; use BackedEnum; use Filament\Pages\Page; use Filament\Support\Icons\Heroicon;
class SalesReport extends Page {
    protected static string|BackedEnum|null $navigationIcon=Heroicon::OutlinedChartBar;
    protected static ?string $navigationLabel='Báo cáo bán hàng';
    protected static string|\UnitEnum|null $navigationGroup='Báo cáo';
    protected static ?int $navigationSort=10;
    protected string $view='filament.pages.sales-report';
    public static function canAccess():bool { $u=auth()->user(); return $u && ($u->isAdministrator()||$u->hasPermission('reports.view')); }
    protected function getViewData():array { $from=request()->filled('from') ? \Illuminate\Support\Carbon::parse(request('from'))->startOfDay() : now()->startOfMonth();$to=request()->filled('to') ? \Illuminate\Support\Carbon::parse(request('to'))->endOfDay() : now()->endOfDay();$payments=Payment::query()->where('status','paid')->whereBetween('paid_at',[$from,$to]);return ['from'=>$from,'to'=>$to,'revenue'=>(int)(clone $payments)->sum('amount'),'orders'=>Order::query()->whereBetween('placed_at',[$from,$to])->count(),'couponRedemptions'=>CouponRedemption::query()->whereBetween('redeemed_at',[$from,$to])->count(),'lowStock'=>Inventory::query()->whereRaw('(quantity_on_hand - quantity_reserved) <= reorder_level')->count(),'bestSellers'=>OrderItem::query()->selectRaw('product_name, sum(quantity) as units, sum(total_amount) as revenue')->whereHas('order',fn($q)=>$q->whereBetween('placed_at',[$from,$to]))->groupBy('product_name')->orderByDesc('units')->limit(8)->get()]; }
}
