<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/3
 * Time: 0:41
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Company;
use App\Models\Admin\GoodsAttr;
use DB;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CompanyinfoController extends Controller
{
    public function index(Request $request){
        if($request->ajax()){
            $sortName = $request->post("sortName");    //排序列名
            $sortOrder = $request->post("sortOrder");   //排序（desc，asc）
            $pageNumber = $request->post("pageNumber");  //当前页码
            $pageSize = $request->post("pageSize");   //一页显示的条数
            $search = $request->post("search",'');  //搜索条件

            $sql = "select c.company_name";
            $sql .= ",(select count(gas.id) from goods_attr as gas where gas.company_id=c.id) as godown_number";
            $sql .= ",(select sum(gs.godown_weight) from goods_attr as gas inner join godown as gs on gs.goods_attr_id=gas.id where gas.company_id=c.id and gs.type=0) as market_h";
            $sql .= ",(select sum(gs.godown_weight) from goods_attr as gas inner join godown as gs on gs.goods_attr_id=gas.id where gas.company_id=c.id and gs.type=1) as market_d";
            $sql .= ",IFNULL((select sum(gs.godown_weight) from goods_attr as gas inner join godown as gs on gs.goods_attr_id=gas.id where gas.company_id=c.id and gs.type=0),0.0)*45+IFNULL((select sum(gs.godown_weight) from goods_attr as gas inner join godown as gs on gs.goods_attr_id=gas.id where gas.company_id=c.id and gs.type=1),0.0) as market_t";
            $sql .= ",(select sum(ss.sale_weight) from goods_attr as gas inner join godown as gs on gs.goods_attr_id=gas.id inner join sale as ss on ss.godown_id=gs.id where gas.company_id=c.id and gs.type=0 and PERIOD_DIFF(date_format(now(),'%Y%m'),date_format(ss.created_at,'%Y%m'))=1) as sale_h";
            $sql .= ",(select sum(ss.sale_weight) from goods_attr as gas inner join godown as gs on gs.goods_attr_id=gas.id inner join sale as ss on ss.godown_id=gs.id where gas.company_id=c.id and gs.type=1 and PERIOD_DIFF(date_format(now(),'%Y%m'),date_format(ss.created_at,'%Y%m'))=1) as sale_d";
            $sql .= ",IFNULL((select sum(ss.sale_weight) from goods_attr as gas inner join godown as gs on gs.goods_attr_id=gas.id inner join sale as ss on ss.godown_id=gs.id where gas.company_id=c.id and gs.type=0 and PERIOD_DIFF(date_format(now(),'%Y%m'),date_format(ss.created_at,'%Y%m'))=1),0.0)*45+IFNULL((select sum(ss.sale_weight) from goods_attr as gas inner join godown as gs on gs.goods_attr_id=gas.id inner join sale as ss on ss.godown_id=gs.id where gas.company_id=c.id and gs.type=1 and PERIOD_DIFF(date_format(now(),'%Y%m'),date_format(ss.created_at,'%Y%m'))=1),0.0) as sale_t";
            $sql .= ",(select sum(ss.sale_total_price) from goods_attr as gas inner join godown as gs on gs.goods_attr_id=gas.id inner join sale as ss on ss.godown_id=gs.id where gas.company_id=c.id and PERIOD_DIFF(date_format(now(),'%Y%m'),date_format(ss.created_at,'%Y%m'))=1) as sale_money ";
            $sql .= " from company as c";

            if($search){
                $sql .= " where c.company_name like '%". $search ."%'";
            }

            $data['total'] = count(DB::select($sql));

            $sql .= " order by ". $sortName ." ". $sortOrder;
            if($pageSize != 'All'){
                $start = ($pageNumber-1)*$pageSize;   //开始位置
                $sql .= " limit ". $start .','. $pageSize;
            }

            $res = DB::select($sql);

            $news = array();

            foreach($res as $k => $v){

                $news[$k]['company_name'] = $v->company_name;
                $news[$k]['godown_number'] = $v->godown_number;
                $news[$k]['market_h'] = sprintf("%.0f",$v->market_h);
                $news[$k]['market_d'] = sprintf("%.0f",$v->market_d);
                $news[$k]['market_t'] = sprintf("%.0f",$v->market_t);
                $news[$k]['sale_h'] = sprintf("%.0f",$v->sale_h);
                $news[$k]['sale_d'] = sprintf("%.0f",$v->sale_d);
                $news[$k]['sale_t'] = sprintf("%.0f",$v->sale_t);
                $news[$k]['sale_money'] = sprintf("%.0f",$v->sale_money);
            }

            $data['rows'] = $news;

            return response()->json($data);

        }
        return view('admin.companyinfo.index');
    }
}