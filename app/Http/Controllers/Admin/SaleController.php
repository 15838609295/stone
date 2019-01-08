<?php

namespace App\Http\Controllers\Admin;

use App\Models\Admin\AdminUser;
use App\Models\Admin\Company;
use App\Models\Admin\Godown;
use App\Models\Admin\GoodsAttr;
use DB;
use Illuminate\Http\Request;
use App\Models\Admin\Sale;
use Carbon\Carbon;

use App\Http\Controllers\Controller;

class SaleController extends Controller{

    public $result = array("status"=>0,'msg'=>'请求成功','data'=>"");
    //企业列表
    public function index(Request $request){
        if ($request->ajax()) {
            $sortName = $request->post("sortName");    //排序列名
            $sortOrder = $request->post("sortOrder");   //排序（desc，asc）
            $pageNumber = $request->post("pageNumber");  //当前页码
            $pageSize = $request->post("pageSize");   //一页显示的条数
            $start = ($pageNumber-1)*$pageSize;   //开始位置
            $search = $request->post("search",'');  //搜索条件

            $total = Sale::from('sale as s')
                ->select('s.*','g.godown_no')
                ->leftJoin('godown as g','s.godown_id','=','g.id');
            $rows = Sale::from('sale as s')
                ->select('s.*','g.godown_no')
                ->leftJoin('godown as g','s.godown_id','=','g.id');

            if(trim($search)){
                $total->where(function ($query) use ($search) {
                    $query->where('g.godown_no', 'LIKE', '%' . $search . '%');
                });
                $rows->where(function ($query) use ($search) {
                    $query->where('g.godown_no', 'LIKE', '%' . $search . '%');
                });
            }

            $data['total'] = $total->count();
            $data['rows'] = $rows->skip($start)->take($pageSize)
                ->orderBy($sortName, $sortOrder)
                ->get();

            return response()->json($data);
        }
        return view('admin.sale.index');
    }

    public function sales(Request $request,$id){
        if ($request->ajax()) {
            $sortName = $request->post("sortName");    //排序列名
            $sortOrder = $request->post("sortOrder");   //排序（desc，asc）
            $pageNumber = $request->post("pageNumber");  //当前页码
            $pageSize = $request->post("pageSize");   //一页显示的条数
            $search = $request->post("search",'');  //搜索条件

            $sql = "select ga.goods_attr_name";
            $sql .= ",(select sum(ss.sale_weight) from godown as gs inner join sale as ss on ss.godown_id=gs.id where ga.id=gs.goods_attr_id and ss.curtype=0) as sale_weight_h";
            $sql .= ",(select count(ss.id) from godown as gs inner join sale as ss on ss.godown_id=gs.id where ga.id=gs.goods_attr_id and ss.curtype=0) as sale_count_h";
            $sql .= ",IFNULL((select sum(ss.sale_total_price) from godown as gs inner join sale as ss on ss.godown_id=gs.id where ga.id=gs.goods_attr_id and ss.curtype=0 and DATE_FORMAT(ss.created_at,'%Y%m')=DATE_FORMAT(CURDATE(),'%Y%m')),0.0)/IFNULL((select count(ss.id) from godown as gs inner join sale as ss on ss.godown_id=gs.id where ga.id=gs.goods_attr_id and ss.curtype=0 and DATE_FORMAT(ss.created_at,'%Y%m')=DATE_FORMAT(CURDATE(),'%Y%m')),0.0) as month_j_h";
            $sql .= ",IFNULL((select sum(ss.sale_total_price) from godown as gs inner join sale as ss on ss.godown_id=gs.id where ga.id=gs.goods_attr_id and ss.curtype=0),0.0)/IFNULL((select count(ss.id) from godown as gs inner join sale as ss on ss.godown_id=gs.id where ga.id=gs.goods_attr_id and ss.curtype=0),0.0) as total_j_h";
            $sql .= ",(select sum(ss.sale_weight) from godown as gs inner join sale as ss on ss.godown_id=gs.id where ga.id=gs.goods_attr_id and ss.curtype=1) as sale_weight_d";
            $sql .= ",(select count(ss.id) from godown as gs inner join sale as ss on ss.godown_id=gs.id where ga.id=gs.goods_attr_id and ss.curtype=1) as sale_count_d";
            $sql .= ",IFNULL((select sum(ss.sale_total_price) from godown as gs inner join sale as ss on ss.godown_id=gs.id where ga.id=gs.goods_attr_id and ss.curtype=1 and DATE_FORMAT(ss.created_at,'%Y%m')=DATE_FORMAT(CURDATE(),'%Y%m')),0.0)/IFNULL((select count(ss.id) from godown as gs inner join sale as ss on ss.godown_id=gs.id where ga.id=gs.goods_attr_id and ss.curtype=1 and DATE_FORMAT(ss.created_at,'%Y%m')=DATE_FORMAT(CURDATE(),'%Y%m')),0.0) as month_j_d";
            $sql .= ",IFNULL((select sum(ss.sale_total_price) from godown as gs inner join sale as ss on ss.godown_id=gs.id where ga.id=gs.goods_attr_id and ss.curtype=1),0.0)/IFNULL((select count(ss.id) from godown as gs inner join sale as ss on ss.godown_id=gs.id where ga.id=gs.goods_attr_id and ss.curtype=1),0.0) as total_j_d";
            $sql .= ",((select sum(ss.sale_total_price) from godown as gs inner join sale as ss on ss.godown_id=gs.id where ga.id=gs.goods_attr_id and DATE_FORMAT(ss.created_at,'%Y%m')=DATE_FORMAT(CURDATE(),'%Y%m'))) as money_month";
            $sql .= ",((select sum(ss.sale_total_price) from godown as gs inner join sale as ss on ss.godown_id=gs.id where ga.id=gs.goods_attr_id and ss.curtype=0)+(select sum(ss.sale_total_price) from godown as gs inner join sale as ss on ss.godown_id=gs.id where ga.id=gs.goods_attr_id and ss.curtype=1)) as money_total";
            $sql .= " from goods_attr as ga where ga.company_id=".$id;

            $data['total'] = count(DB::select($sql));

            $sql .= " order by ". $sortName ." ". $sortOrder;
            if($pageSize != 'All'){
                $start = ($pageNumber-1)*$pageSize;   //开始位置
                $sql .= " limit ". $start .','. $pageSize;
            }

            $res = DB::select($sql);
            $total = array();

            if($res){
                foreach ($res as $k => $v){
                    $total[$k]['goods_attr_name'] = $v->goods_attr_name;
                    $total[$k]['sale_weight_h'] = $v->sale_weight_h;
                    $total[$k]['sale_count_h'] = $v->sale_count_h;
                    $total[$k]['month_j_h'] = $v->month_j_h;
                    $total[$k]['total_j_h'] = $v->total_j_h;
                    $total[$k]['sale_weight_d'] = $v->sale_weight_d;
                    $total[$k]['sale_count_d'] = $v->sale_count_d;
                    $total[$k]['month_j_d'] = $v->month_j_d;
                    $total[$k]['total_j_d'] = $v->total_j_d;
                    $total[$k]['money_month'] = $v->money_month;
                    $total[$k]['money_total'] = $v->money_total;
                }
            }

            $new = array(
                'goods_attr_name' => '合计',
                'sale_weight_h' => 0,
                'sale_count_h' => 0,
                'sale_month_h' => 0,
                'sale_weight_d' => 0,
                'sale_count_d' => 0,
                'money_month' => 0,
                'money_total' => 0,
            );

            foreach ($total as $k => $v){

                $total[$k]['sale_weight_h'] = sprintf("%.4f", $v['sale_weight_h']);
                $total[$k]['sale_count_h'] = sprintf("%.0f", $v['sale_count_h']);
                $total[$k]['sale_weight_d'] = sprintf("%.4f", $v['sale_weight_d']);
                $total[$k]['sale_count_d'] = sprintf("%.0f", $v['sale_count_d']);
                $total[$k]['month_j_h'] = sprintf("%.4f", $v['month_j_h']);
                $total[$k]['total_j_h'] = sprintf("%.4f", $v['total_j_h']);
                $total[$k]['month_j_d'] = sprintf("%.4f", $v['month_j_d']);
                $total[$k]['total_j_d'] = sprintf("%.2f", $v['total_j_d']);
                $total[$k]['money_month'] = sprintf("%.2f", $v['money_month']);
                $total[$k]['money_total'] = sprintf("%.2f", $v['money_total']);

                $new['sale_weight_h'] += $v['sale_weight_h'];
                $new['sale_count_h'] += $v['sale_count_h'];
                $new['sale_weight_d'] += $v['sale_weight_d'];
                $new['sale_count_d'] += $v['sale_count_d'];
                $new['money_month'] += $v['money_month'];
                $new['money_total'] += $v['money_total'];

            }

            $new['sale_weight_h'] = sprintf("%.4f", $new['sale_weight_h']);
            $new['sale_count_h'] = sprintf("%.0f", $new['sale_count_h']);
            $new['sale_weight_d'] = sprintf("%.4f", $new['sale_weight_d']);
            $new['sale_count_d'] = sprintf("%.0f", $new['sale_count_d']);
            $new['money_month'] = sprintf("%.2f", $new['money_month']);
            $new['money_total'] = sprintf("%.2f", $new['money_total']);

            array_unshift($total,$new);

            $data['total'] = count($total);
            $data['rows'] = $total;

            return response()->json($data);

        }
        return view('admin.company.sales',array('id' => $id,'name' => Company::find($id)->company_name));
    }

    public function salelog(Request $request,$id){
        if($request->ajax()){
            $sortName = $request->post("sortName");    //排序列名
            $sortOrder = $request->post("sortOrder");   //排序（desc，asc）
            $pageNumber = $request->post("pageNumber");  //当前页码
            $pageSize = $request->post("pageSize");   //一页显示的条数
            $search = $request->post("search",'');  //搜索条件

            $goodsattr = GoodsAttr::from('goods_attr as ga')
                ->select('g.id')
                ->leftJoin('godown as g','g.goods_attr_id','=','ga.id')
                ->where('ga.company_id','=',$id)
                ->get();

            if(!$goodsattr){
                return response()->json(['total'=>0,'rows'=>'']);
            }

            $godown_ids = array();
            foreach ($goodsattr as $v){
                $godown_ids[] = $v['id'];
            }

            $query = Sale::whereIn('godown_id',$godown_ids)->orderBy($sortName, $sortOrder);

            if($pageSize != 'All'){
                $start = ($pageNumber-1)*$pageSize;   //开始位置
                $query->limit($start, $pageSize);
            }

            $total = $query->get();
            if(!$total){
                return response()->json(['total'=>0,'rows'=>'']);
            }

            $new = array();
            $del = array();
            $total = $total->toArray();

            foreach ($total as $k => $v){
                if($k > 0 && substr($v['created_at'],0,7)== substr($total[$k-1]['created_at'],0,7)){
                    if($v['curtype']==0){
                        $new[$k]['cur_time'] = substr($v['created_at'],0,7);
                        $new[$k]['weight_h'] = $new[$k-1]['weight_h']+$v['sale_weight'];
                        $new[$k]['order_number_h'] = $new[$k-1]['order_number_h']+1;
                        $new[$k]['sale_total_price_h'] = $new[$k-1]['sale_total_price_h']+$v['sale_total_price'];
                        $new[$k]['weight_d'] = $new[$k-1]['weight_d']+0;
                        $new[$k]['order_number_d'] = $new[$k-1]['order_number_d']+0;
                        $new[$k]['sale_total_price_d'] = $new[$k-1]['sale_total_price_d']+0;
                    }else{
                        $new[$k]['cur_time'] = substr($v['created_at'],0,7);
                        $new[$k]['weight_h'] = $new[$k-1]['weight_h']+0;
                        $new[$k]['order_number_h'] = $new[$k-1]['order_number_h']+0;
                        $new[$k]['sale_total_price_h'] = $new[$k-1]['sale_total_price_h']+0;
                        $new[$k]['weight_d'] = $new[$k-1]['weight_d']+$v['sale_weight'];
                        $new[$k]['order_number_d'] = $new[$k-1]['order_number_d']+1;
                        $new[$k]['sale_total_price_d'] = $new[$k-1]['sale_total_price_d']+$v['sale_total_price'];
                    }
                    $del[] = $k-1;
                }else{
                    if($v['curtype']==0){
                        $new[$k]['cur_time'] = substr($v['created_at'],0,7);
                        $new[$k]['weight_h'] = $v['sale_weight'];
                        $new[$k]['order_number_h'] = 1;
                        $new[$k]['sale_total_price_h'] = $v['sale_total_price'];
                        $new[$k]['weight_d'] = 0;
                        $new[$k]['order_number_d'] = 0;
                        $new[$k]['sale_total_price_d'] = 0;
                    }else{
                        $new[$k]['cur_time'] = substr($v['created_at'],0,7);
                        $new[$k]['weight_h'] = 0;
                        $new[$k]['order_number_h'] = 0;
                        $new[$k]['sale_total_price_h'] = 0;
                        $new[$k]['weight_d'] = $v['sale_weight'];
                        $new[$k]['order_number_d'] = 1;
                        $new[$k]['sale_total_price_d'] = $v['sale_total_price'];
                    }

                }
            }

            foreach ($del as $v){
                unset($new[$v]);
            }
            $new = array_values($new);

            foreach ($new as $k => $v){
                if($v['weight_h']==0){
                    $new[$k]['price_j_h'] = 0;
                }else{
                    $new[$k]['price_j_h'] = round($v['sale_total_price_h']/$v['weight_h']);
                }
                if($v['weight_d']==0){
                    $new[$k]['price_j_d'] = 0;
                }else{
                    $new[$k]['price_j_d'] = round($v['sale_total_price_d']/$v['weight_d']);
                }
            }

            $heji = array(
                'cur_time' => '合计',
                'weight_h' => 0,
                'sale_total_price_h' => 0,
                'order_number_h' => 0,
                'price_j_h' => '',
                'weight_d' => 0,
                'sale_total_price_d' => 0,
                'order_number_d' => 0,
                'price_j_d' => '',
            );

            foreach ($new as $k => $v){

                $new[$k]['weight_h'] = sprintf("%.4f", $v['weight_h']);
                $new[$k]['sale_total_price_h'] = sprintf("%.2f", $v['sale_total_price_h']);
                $new[$k]['order_number_h'] = sprintf("%.0f", $v['order_number_h']);
                $new[$k]['weight_d'] = sprintf("%.4f", $v['weight_d']);
                $new[$k]['sale_total_price_d'] = sprintf("%.2f", $v['sale_total_price_d']);
                $new[$k]['order_number_d'] = sprintf("%.0f", $v['order_number_d']);


                $heji['weight_h'] += $v['weight_h'];
                $heji['sale_total_price_h'] += $v['sale_total_price_h'];
                $heji['order_number_h'] += $v['order_number_h'];
                $heji['weight_d'] += $v['weight_d'];
                $heji['sale_total_price_d'] += $v['sale_total_price_d'];
                $heji['order_number_d'] += $v['order_number_d'];
            }

            $heji['weight_h'] = sprintf("%.4f", $heji['weight_h']);
            $heji['sale_total_price_h'] = sprintf("%.2f", $heji['sale_total_price_h']);
            $heji['order_number_h'] = sprintf("%.0f", $heji['order_number_h']);
            $heji['weight_d'] = sprintf("%.4f", $heji['weight_d']);
            $heji['sale_total_price_d'] = sprintf("%.2f", $heji['sale_total_price_d']);
            $heji['order_number_d'] = sprintf("%.0f", $heji['order_number_d']);

            array_unshift($new,$heji);

            $data['total'] = count($new);
            $data['rows'] = $new;

            return response()->json($data);

        }
        return view('admin.company.salelog',array('id' => $id,'name' => Company::find($id)->company_name));
    }

}
