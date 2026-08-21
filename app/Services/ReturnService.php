<?php
namespace App\Services;
use App\Models\ReturnRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class ReturnService {
    public function __construct(private OrderStatusService $orders, private AdminActivityLogger $activity) {}
    public function transition(ReturnRequest $request, string $target): ReturnRequest {
        $allowed = ['requested'=>['approved','rejected'], 'approved'=>['received'], 'received'=>['refunded','closed'], 'refunded'=>['closed'], 'rejected'=>[], 'closed'=>[]];
        return DB::transaction(function () use ($request,$target,$allowed) {
            $locked = ReturnRequest::query()->with('order')->lockForUpdate()->findOrFail($request->id);
            if (!in_array($target,$allowed[$locked->status] ?? [],true)) throw ValidationException::withMessages(['status'=>'Chuyển trạng thái đổi trả không hợp lệ.']);
            $before=$locked->only(['status','approved_at']); $changes=['status'=>$target]; if($target==='approved')$changes['approved_at']=now(); $locked->update($changes);
            if ($target==='refunded' && in_array('returned',$this->orders->nextStatuses($locked->order),true)) $this->orders->transition($locked->order,'returned',auth()->user(),'Đã hoàn tiền theo yêu cầu đổi trả '.$locked->return_number.'.');
            $this->activity->log('return.status_changed',$locked,$before,$locked->fresh()->only(['status','approved_at'])); return $locked->fresh();
        });
    }
}
