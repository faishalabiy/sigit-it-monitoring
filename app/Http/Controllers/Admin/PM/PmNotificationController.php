<?php

namespace App\Http\Controllers\Admin\PM;

use App\Http\Controllers\Controller;
use App\Models\PmNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PmNotificationController extends Controller {
    public function index(Request $request):View {
        $notifications=PmNotification::where('user_id',$request->user()->id)
            ->latest('id')->paginate(20);
        $unread=PmNotification::where('user_id',$request->user()->id)
            ->whereNull('read_at')->count();
        return view('admin.pm.notifications.index',compact('notifications','unread'));
    }
    public function markRead(Request $request,PmNotification $notification):RedirectResponse {
        abort_unless($notification->user_id===$request->user()->id,404);
        if(!$notification->read_at) $notification->update(['read_at'=>now()]);
        return back()->with('success','Notifikasi ditandai sudah dibaca.');
    }
}