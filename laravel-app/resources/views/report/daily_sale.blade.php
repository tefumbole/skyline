@extends('layout.main')
@section('content')
<section>
	<div class="container-fluid">
		<div class="card"> 
			<div class="card-body"> 
				{{ Form::open(['route' => ['report.dailySaleByWarehouse', $year, $month], 'method' => 'post', 'id' => 'report-form']) }}
				<input type="hidden" name="warehouse_id_hidden" value="{{$warehouse_id}}">
				<h4 class="text-center">{{trans('file.Daily Sale Report')}} &nbsp;&nbsp;
				<select class="selectpicker" id="warehouse_id" name="warehouse_id">
					<option value="0">{{trans('file.All Warehouse')}}</option>
					@foreach($lims_warehouse_list as $warehouse)
					<option value="{{$warehouse->id}}">{{$warehouse->name}}</option>
					@endforeach
				</select>
				</h4>
				{{ Form::close() }}
				@php
					$monthStart = sprintf('%04d-%02d-01', $year, $month);
					$monthEnd = sprintf('%04d-%02d-%02d', $year, $month, $number_of_day);
					$monthSales = url('report/sales').'?start_date='.$monthStart.'&end_date='.$monthEnd.'&warehouse_id='.$warehouse_id;
				@endphp
				<p class="text-center mt-3 mb-0">
					<a class="btn btn-sm btn-outline-secondary" href="{{ $monthSales }}">Detailed sale report for this month</a>
					<a class="btn btn-sm btn-outline-secondary" href="{{ $monthSales }}&export=pdf">Export PDF</a>
				</p>
				<div class="table-responsive mt-4">
					<table class="table table-bordered" style="border-top: 1px solid #dee2e6; border-bottom: 1px solid #dee2e6;">
						<thead>
							<tr>
								<th><a href="{{url('report/daily_sale/'.$prev_year.'/'.$prev_month)}}"><i class="fa fa-arrow-left"></i> {{trans('file.Previous')}}</a></th>
						    	<th colspan="5" class="text-center">{{date("F", strtotime($year.'-'.$month.'-01')).' ' .$year}}</th>
						    	<th><a href="{{url('report/daily_sale/'.$next_year.'/'.$next_month)}}">{{trans('file.Next')}} <i class="fa fa-arrow-right"></i></a></th>
						    </tr>
						</thead>
					    <tbody>
						    <tr>
							    <td><strong>Sunday</strong></td>
							    <td><strong>Monday</strong></td>
							    <td><strong>Tuesday</strong></td>
							    <td><strong>Wednesday</strong></td>
							    <td><strong>Thrusday</strong></td>
							    <td><strong>Friday</strong></td>
							    <td><strong>Saturday</strong></td>
						    </tr>
						    <?php 
						    	$i = 1;
						    	$flag = 0;
						    	while ($i <= $number_of_day) {
						    		echo '<tr>';
						    		for($j=1 ; $j<=7 ; $j++){
						    			if($i > $number_of_day)
						    				break;

						    			if($flag){
						    				$dayDate = sprintf('%04d-%02d-%02d', $year, $month, $i);
						    				$dayUrl = url('report/sales').'?start_date='.$dayDate.'&end_date='.$dayDate.'&warehouse_id='.$warehouse_id;
						    				if($year.'-'.$month.'-'.$i == date('Y').'-'.date('m').'-'.(int)date('d'))
						    					echo '<td><p style="color:red"><strong><a href="'.$dayUrl.'">'.$i.'</a></strong></p>';
						    				else
						    					echo '<td><p><strong><a href="'.$dayUrl.'">'.$i.'</a></strong></p>';

						    				if($total_discount[$i]){
						    					echo '<strong>'.trans("file.Product Discount").'</strong><br><span>'.$total_discount[$i].'</span><br><br>';
						    				}
						    				if($order_discount[$i]){
						    					echo '<strong>'.trans("file.Order Discount").'</strong><br><span>'.$order_discount[$i].'</span><br><br>';
						    				}
						    				if($total_tax[$i]){
						    					echo '<strong>'.trans("file.Product Tax").'</strong><br><span>'.$total_tax[$i].'</span><br><br>';
						    				}
						    				if($order_tax[$i]){
						    					echo '<strong>'.trans("file.Order Tax").'</strong><br><span>'.$order_tax[$i].'</span><br><br>';
						    				}
						    				if($shipping_cost[$i]){
						    					echo '<strong>'.trans("file.Shipping Cost").'</strong><br><span>'.$shipping_cost[$i].'</span><br><br>';
						    				}
						    				if($grand_total[$i]){
						    					echo '<strong><a href="'.$dayUrl.'">'.trans("file.grand total").'</a></strong><br><span>'.$grand_total[$i].'</span><br><br>';
						    				}
						    				echo '</td>';
						    				$i++;
						    			}
						    			elseif($j == $start_day){
						    				$dayDate = sprintf('%04d-%02d-%02d', $year, $month, $i);
						    				$dayUrl = url('report/sales').'?start_date='.$dayDate.'&end_date='.$dayDate.'&warehouse_id='.$warehouse_id;
						    				if($year.'-'.$month.'-'.$i == date('Y').'-'.date('m').'-'.(int)date('d'))
						    					echo '<td><p style="color:red"><strong><a href="'.$dayUrl.'">'.$i.'</a></strong></p>';
						    				else
						    					echo '<td><p><strong><a href="'.$dayUrl.'">'.$i.'</a></strong></p>';

						    				if($total_discount[$i]){
						    					echo '<strong>'.trans("file.Product Discount").'</strong><br><span>'.$total_discount[$i].'</span><br><br>';
						    				}
						    				if($order_discount[$i]){
						    					echo '<strong>'.trans("file.Order Discount").'</strong><br><span>'.$order_discount[$i].'</span><br><br>';
						    				}
						    				if($total_tax[$i]){
						    					echo '<strong>'.trans("file.Product Tax").'</strong><br><span>'.$total_tax[$i].'</span><br><br>';
						    				}
						    				if($order_tax[$i]){
						    					echo '<strong>'.trans("file.Order Tax").'</strong><br><span>'.$order_tax[$i].'</span><br><br>';
						    				}
						    				if($shipping_cost[$i]){
						    					echo '<strong>'.trans("file.Shipping Cost").'</strong><br><span>'.$shipping_cost[$i].'</span><br><br>';
						    				}
						    				if($grand_total[$i]){
						    					echo '<strong><a href="'.$dayUrl.'">'.trans("file.grand total").'</a></strong><br><span>'.$grand_total[$i].'</span><br><br>';
						    				}
						    				echo '</td>';
						    				$flag = 1;
						    				$i++;
						    				continue;
						    			}
						    			else {
						    				echo '<td></td>';
						    			}
						    		}
						    	    echo '</tr>';
						    	}
						    ?>
					    </tbody>
					</table>
				</div>
			</div>
		</div>	
	</div>
</section>

<script type="text/javascript">

	$("ul#report").siblings('a').attr('aria-expanded','true');
    $("ul#report").addClass("show");
    $("ul#report #daily-sale-report-menu").addClass("active");

	$('#warehouse_id').val($('input[name="warehouse_id_hidden"]').val());
	$('.selectpicker').selectpicker('refresh');

	$('#warehouse_id').on("change", function(){
		$('#report-form').submit();
	});
</script>
@endsection