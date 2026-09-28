<?php
namespace Modules\DistributionNew\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\DistributionNew\Models\DisnewDeliveryProof;
class DisnewProofController extends Controller { public function store(Request $request){ $data=$request->except('proof_file'); if($request->hasFile('proof_file')){ $data['proof_file']=$request->file('proof_file')->store('distributionnew/proofs','public'); } DisnewDeliveryProof::create($data); return back()->with('status','Delivery proof saved.'); } }
