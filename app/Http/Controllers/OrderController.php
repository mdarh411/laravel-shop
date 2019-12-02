<?php

namespace App\Http\Controllers;
use PDF;
use App\Order;
use App\Cart;
use App\Payment;
use App\User;
use Illuminate\Http\Request;
use Notification;
use App\Notifications\ShopNotification;

use App\Http\Resources\OrderCollection;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $order = Order::first();
        return view('admin.order.index')->with('order', $order);
        
    }    

    public function pdf(Request $request)
    {
        $order = Order::find($request->id);
        // return view('admin.order.pdf', compact('order'));

        $file_name = $order->order_number.'_'.$order->first_name.'.pdf';
        $pdf = PDF::loadView('admin.order.pdf', compact('order') );
        return $pdf->download($file_name);
        
    }


    public function ordersList()
    {   
        return new OrderCollection( Order::all() );
        // $orders = Order::orderBy('status', 'desc')->get()->toArray();
        // return datatables()->of($orders)->make(true);
    }

    public function ordersTrack(Request $request)
    {   

        $order = Order::where('order_number', $request->order_number)->first();
        if ($order) {
            return back()->with('success','Order: '.$order->status);
        }else{
            return back()->withErrors('Invalid order number!.');
        }
    }

    public function ordersTrackIndex()
    {   



        $users = User::where('is_admin', 1)->get();
        $details = [
            'title' => 'New order created!',
            'actionURL' => route('admin'),
            'fas' => 'fa-file-alt'
        ];
        Notification::send($users, new ShopNotification($details));






        return view('shop.track');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $request->validate([
            'first_name'      =>  'required',
            'last_name'      =>  'required',
            'address'      =>  'required',
            'city'      =>  'required',
            'country'      =>  'required|string',
            'post_code'      =>  'required',
            'phone_number'      =>  'required',
            'shipping'      =>  'required',
            'paymentoption'      =>  'required|in:cash,bKash,rocket',
            // 'transectionId'      =>  'string|min:3',
        ]);

        if ( empty(Cart::where('user_id', auth()->user()->id)->where('order_id', null)->first()) ) {
            return back()->withErrors('Cart empty!');
        }

        $payment_method = $request->paymentoption;
        $transectionId = null;

        if ($payment_method === 'bKash' || $payment_method === 'rocket') {   
            if(empty($request->transectionId)) return back()->withErrors('Transection id empty!');
            $transectionId = $request->transectionId;
        }

        $order = new Order;
        $order->order_number = 'ORD-'.strtoupper(uniqid());
        $order->user_id = auth()->user()->id;

        $order->status = 'pending';

        $order->shipping_id = $request->shipping;
        $order->first_name = $request->first_name;
        $order->last_name = $request->last_name;
        $order->address = $request->address;
        $order->city = $request->city;
        $order->country = $request->country;
        $order->post_code = $request->post_code;
        $order->phone_number = $request->phone_number;
        $order->notes = $request->notes;
        $order->save();
        Cart::where('user_id', auth()->user()->id)->where('order_id', null)->update(['order_id' => $order->id]);

        $payment = new Payment;
        $payment->user_id = auth()->user()->id;
        $payment->order_id = $order->id;

        $payment->payment_method = $payment_method;
        $payment->transection_id = $transectionId;
        $payment->status = 'unpaid';
        $payment->save();
        Order::find($order->id)->where('user_id', auth()->user()->id)->update(['payment_id' => $payment->id]);


        $users = User::where('is_admin', 1)->get();
        $details = [
            'title' => 'New order created!',
            'actionURL' => route('admin.product.order.show', $order->id),
            'fas' => 'fa-file-alt'
        ];
        Notification::send($users, new ShopNotification($details));




        return redirect()->route('shop')->with('success','Your order success!.');
    }



    /**
     * Display the specified resource.
     *
     * @param  \App\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        $order = Order::find($request->id);
        return view('admin.order.show')->with('order', $order);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request)
    {
        $order = Order::find($request->id);
        return view('admin.order.edit')->with('order', $order);

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Order $order)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $order = Order::find($request->id);
        if ($order) {
            $order->delete();
            return redirect()->route('admin.product.order')->with('success','You have successfully delete an order.');
        }else{
            return redirect()->route('admin.product.order')->withErrors('Invalid order!.');
        }
    }
}
