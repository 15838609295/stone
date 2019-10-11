<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/2
 * Time: 20:47
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Godown;
use App\Models\Admin\GoodsAttr;
use App\Models\Admin\Sale;
use DB;
use Illuminate\Http\Request;
use Carbon\Carbon;

class MarketController extends Controller
{
    public function godownList(Request $request){
        if ($request->ajax()) {
            $sortName = $request->post("sortName");    //排序列名
            $sortOrder = $request->post("sortOrder");   //排序（desc，asc）
            $pageNumber = $request->post("pageNumber");  //当前页码
            $pageSize = $request->post("pageSize");   //一页显示的条数
            $search = $request->post("search",'');  //搜索条件

            $sql = "select ga.goods_attr_name,ga.goods_attr_name,ga.authentication,ga.status";
            $sql .= ",(select count(gas.id) from goods_attr as gas where gas.goods_attr_name=ga.goods_attr_name) as company_number";
            $sql .= ",IFNULL((select sum(gs.godown_weight) from goods_attr as gas inner join godown as gs on gas.id=gs.goods_attr_id where gas.goods_attr_name=ga.goods_attr_name and gs.type=0),0.0) as market_h";
            $sql .= ",IFNULL((select sum(gs.godown_weight) from goods_attr as gas inner join godown as gs on gas.id=gs.goods_attr_id where gas.goods_attr_name=ga.goods_attr_name and gs.type=1),0.0) as market_d";
            $sql .= ",IFNULL((select sum(ss.sale_weight) from goods_attr as gas inner join godown as gs on gas.id=gs.goods_attr_id inner join sale as ss on gs.id=ss.godown_id where gas.goods_attr_name=ga.goods_attr_name and gs.type=0 and PERIOD_DIFF(date_format(now(),'%Y%m'),date_format(ss.created_at,'%Y%m'))=1),0.0) as sale_h";
            $sql .= ",IFNULL((select sum(ss.sale_weight) from goods_attr as gas inner join godown as gs on gas.id=gs.goods_attr_id inner join sale as ss on gs.id=ss.godown_id where gas.goods_attr_name=ga.goods_attr_name and gs.type=1 and PERIOD_DIFF(date_format(now(),'%Y%m'),date_format(ss.created_at,'%Y%m'))=1),0.0) as sale_d";
            $sql .= ",IFNULL((select sum(ss.sale_total_price) from goods_attr as gas inner join godown as gs on gas.id=gs.goods_attr_id inner join sale as ss on gs.id=ss.godown_id where gas.goods_attr_name=ga.goods_attr_name and PERIOD_DIFF(date_format(now(),'%Y%m'),date_format(ss.created_at,'%Y%m'))=1),0.0) as sale_t";
            $sql .= ",IFNULL((select sum(ss.sale_total_price) from goods_attr as gas inner join godown as gs on gas.id=gs.goods_attr_id inner join sale as ss on gs.id=ss.godown_id where gas.goods_attr_name=ga.goods_attr_name),0.0) as money_t";
            $sql .= " from goods_attr as ga";

            if($search){
                $sql .= " where ga.goods_attr_name like '%".$search."%'";
            }

            $sql .= " group by ga.goods_attr_name";

            $data['total'] = count(DB::select($sql));

            $sql .= " order by ". $sortName ." ". $sortOrder;
            if($pageSize != 'All'){
                $start = ($pageNumber-1)*$pageSize;   //开始位置
                $sql .= " limit ". $start .','. $pageSize;
            }

            $res = DB::select($sql);
            $news = array();

            foreach ($res as $k => $v){
                $news[$k]['goods_attr_name'] = $v->goods_attr_name;
                $news[$k]['company_number'] = $v->company_number;
                $news[$k]['market_h'] = sprintf('%.0f', $v->market_h);
                $news[$k]['market_d'] = sprintf('%.0f', $v->market_d);
                $news[$k]['sale_h'] = sprintf('%.0f', $v->sale_h);
                $news[$k]['sale_d'] = sprintf('%.0f', $v->sale_d);
                $news[$k]['sale_t'] = sprintf('%.0f', $v->sale_t);
                $news[$k]['money_t'] = sprintf('%.0f', $v->money_t);
                $news[$k]['authentication'] = sprintf('%.0f', $v->authentication);
                $news[$k]['status'] = sprintf('%.0f', $v->status);
            }

            $data['rows'] = $news;
            return response()->json($data);

        }
        return view('admin.market.godownlist');
    }

    public function info(Request $request){
        if($request->ajax()){
            $sortName = $request->post("sortName");    //排序列名
            $sortOrder = $request->post("sortOrder");   //排序（desc，asc）
            $pageNumber = $request->post("pageNumber");  //当前页码
            $pageSize = $request->post("pageSize");   //一页显示的条数
            $start = ($pageNumber-1)*$pageSize;   //开始位置
            $search = $request->post("search",'');  //搜索条件

            if($sortName == 'id'){
                $sortName = 'ga.id';
            }

            $sql = "select ga.goods_attr_name,c.company_name";
            $sql .= ",IFNULL((select sum(ss.sale_weight) from goods_attr as gas inner join godown as gs on gas.id=gs.goods_attr_id inner join sale as ss on gs.id=ss.godown_id where gas.goods_attr_name=ga.goods_attr_name and gas.company_id=ga.company_id and gs.type=0),0.0) as sale_h";
            $sql .= ",IFNULL((select sum(ss.sale_total_price) from goods_attr as gas inner join godown as gs on gas.id=gs.goods_attr_id inner join sale as ss on gs.id=ss.godown_id where gas.goods_attr_name=ga.goods_attr_name and gas.company_id=ga.company_id and gs.type=0 and PERIOD_DIFF(date_format(now(),'%Y%m'),date_format(ss.created_at,'%Y%m'))=1),0.0)/IFNULL((select sum(ss.sale_weight) from goods_attr as gas inner join godown as gs on gas.id=gs.goods_attr_id inner join sale as ss on gs.id=ss.godown_id where gas.goods_attr_name=ga.goods_attr_name and gas.company_id=ga.company_id and gs.type=0 and PERIOD_DIFF(date_format(now(),'%Y%m'),date_format(ss.created_at,'%Y%m'))=1),0.0) as last_j_h";
            $sql .= ",IFNULL((select sum(ss.sale_total_price) from goods_attr as gas inner join godown as gs on gas.id=gs.goods_attr_id inner join sale as ss on gs.id=ss.godown_id where gas.goods_attr_name=ga.goods_attr_name and gas.company_id=ga.company_id and gs.type=0),0.0)/IFNULL((select sum(ss.sale_weight) from goods_attr as gas inner join godown as gs on gas.id=gs.goods_attr_id inner join sale as ss on gs.id=ss.godown_id where gas.goods_attr_name=ga.goods_attr_name and gas.company_id=ga.company_id and gs.type=0),0.0) as j_h";
            $sql .= ",IFNULL((select sum(ss.sale_total_price) from goods_attr as gas inner join godown as gs on gas.id=gs.goods_attr_id inner join sale as ss on gs.id=ss.godown_id where gas.goods_attr_name=ga.goods_attr_name and gas.company_id=ga.company_id and gs.type=0),0.0) as money_h";
            $sql .= ",IFNULL((select sum(ss.sale_weight) from goods_attr as gas inner join godown as gs on gas.id=gs.goods_attr_id inner join sale as ss on gs.id=ss.godown_id where gas.goods_attr_name=ga.goods_attr_name and gas.company_id=ga.company_id and gs.type=1),0.0) as sale_d";
            $sql .= ",IFNULL((select sum(ss.sale_total_price) from goods_attr as gas inner join godown as gs on gas.id=gs.goods_attr_id inner join sale as ss on gs.id=ss.godown_id where gas.goods_attr_name=ga.goods_attr_name and gas.company_id=ga.company_id and gs.type=1 and PERIOD_DIFF(date_format(now(),'%Y%m'),date_format(ss.created_at,'%Y%m'))=1),0.0)/IFNULL((select sum(ss.sale_weight) from goods_attr as gas inner join godown as gs on gas.id=gs.goods_attr_id inner join sale as ss on gs.id=ss.godown_id where gas.goods_attr_name=ga.goods_attr_name and gas.company_id=ga.company_id and gs.type=1 and PERIOD_DIFF(date_format(now(),'%Y%m'),date_format(ss.created_at,'%Y%m'))=1),0.0) as last_j_d";
            $sql .= ",IFNULL((select sum(ss.sale_total_price) from goods_attr as gas inner join godown as gs on gas.id=gs.goods_attr_id inner join sale as ss on gs.id=ss.godown_id where gas.goods_attr_name=ga.goods_attr_name and gas.company_id=ga.company_id and gs.type=1),0.0)/IFNULL((select sum(ss.sale_weight) from goods_attr as gas inner join godown as gs on gas.id=gs.goods_attr_id inner join sale as ss on gs.id=ss.godown_id where gas.goods_attr_name=ga.goods_attr_name and gas.company_id=ga.company_id and gs.type=1),0.0) as j_d";
            $sql .= ",IFNULL((select sum(ss.sale_total_price) from goods_attr as gas inner join godown as gs on gas.id=gs.goods_attr_id inner join sale as ss on gs.id=ss.godown_id where gas.goods_attr_name=ga.goods_attr_name and gas.company_id=ga.company_id and gs.type=1),0.0) as money_d";
            $sql .= ",IFNULL((select sum(ss.sale_total_price) from goods_attr as gas inner join godown as gs on gas.id=gs.goods_attr_id inner join sale as ss on gs.id=ss.godown_id where gas.goods_attr_name=ga.goods_attr_name and gas.company_id=ga.company_id),0.0) as total_price";
            $sql .= " from goods_attr as ga inner join company as c on c.id=ga.company_id";
            $sql .= " where ga.goods_attr_name='".$request->get('goods_attr_name')."'";

            if($search){
                $sql .= " and c.company_name like '%". $search ."%'";
            }

            $data['total'] = count(DB::select($sql));

            $sql .= " order by ". $sortName ." ". $sortOrder;
            if($pageSize != 'All'){
                $start = ($pageNumber-1)*$pageSize;   //开始位置
                $sql .= " limit ". $start .','. $pageSize;
            }

            $res = DB::select($sql);
            $news = array();

            foreach ($res as $k => $v){
                $news[$k]['goods_attr_name'] = $v->goods_attr_name;
                $news[$k]['company_name'] = $v->company_name;
                $news[$k]['sale_h'] = $v->sale_h;
                $news[$k]['last_j_h'] = sprintf('%.2f', $v->last_j_h);
                $news[$k]['j_h'] = sprintf('%.2f', $v->j_h);
                $news[$k]['money_h'] = sprintf('%.2f', $v->money_h);
                $news[$k]['sale_d'] = sprintf('%.2f', $v->sale_d);
                $news[$k]['last_j_d'] = sprintf('%.2f', $v->last_j_d);
                $news[$k]['j_d'] = sprintf('%.2f', $v->j_d);
                $news[$k]['money_d'] = sprintf('%.2f', $v->money_d);
                $news[$k]['total_price'] = sprintf('%.2f', $v->total_price);
            }

            $data['rows'] = $news;
            return response()->json($data);

        }
        return view('admin.market.info',['goods_attr_name'=>$request->goods_attr_name]);
    }
  
    //添加认证
    public function authentication(Request $request){
        $name = $request->input('name','');
        $where['goods_attr_name'] = $name;
        $data['authentication'] = 1;
        $data['updated_at'] = Carbon::now()->toDateTimeString();
        $res = DB::table('goods_attr')->where($where)->update($data);
        if (!$res){
            $result['status'] = 1;
        }else{
            $result['status'] = 0;
        }
        return response()->json($result);
    }

    //取消认证
    public function unsetauthentication(Request $request){
        $name = $request->input('name','');
        $where['goods_attr_name'] = $name;
        $data['authentication'] = 0;
        $data['updated_at'] = Carbon::now()->toDateTimeString();
        $res = DB::table('goods_attr')->where($where)->update($data);
        if (!$res){
            $result['status'] = 1;
        }else{
            $result['status'] = 0;
        }
        return response()->json($result);
    }

    //上架
    public function upperShelf(Request $request){
        $name = $request->input('name','');
        $where['goods_attr_name'] = $name;
        $data['status'] = 1;
        $data['updated_at'] = Carbon::now()->toDateTimeString();
        $res = DB::table('goods_attr')->where($where)->update($data);
        if (!$res){
            $result['status'] = 1;
        }else{
            $result['status'] = 0;
        }
        return response()->json($result);
    }

    //下架
    public function lowerShelf(Request $request){
        $name = $request->input('name','');
        $where['goods_attr_name'] = $name;
        $data['status'] = 0;
        $data['updated_at'] = Carbon::now()->toDateTimeString();
        $res = DB::table('goods_attr')->where($where)->update($data);
        if (!$res){
            $result['status'] = 1;
        }else{
            $result['status'] = 0;
        }
        return response()->json($result);
    }
}
