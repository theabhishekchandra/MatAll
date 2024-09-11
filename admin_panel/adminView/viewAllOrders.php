<div id="ordersBtn" >
  <h2>Order Details</h2>
  <table class="table table-striped">
    <thead>
      <tr>
        <th>O.N.</th>
        <th>Customer</th>
        <th>Contact</th>
        <th>OrderDate</th>
        <th>Payment Method</th>
        <th>Order Status</th>
        <th>Payment Status</th>
        <th>More Details</th>
     </tr>
    </thead>
     <?php
      include_once "../config/dbconnect.php";
      $sql="SELECT * from orders";
      $result=$conn-> query($sql);
      
      if ($result-> num_rows > 0){
        while ($row=$result-> fetch_assoc()) {
    ?>
       <tr>
          <td><?=$row["order_id"]?></td>
          <td><?=$row["name"]?></td>
          <td><?=$row["phone"]?></td>
          <td><?=$row["order_date"]?></td>
          <td><?=$row["payment_method"]?></td>
           <?php 
              if($row["order_status"]=="placed"){
                            
            ?>
                
              <td><button class="btn btn-info" onclick="ChangeOrderStatus('<?=$row['order_id']?>')">Placed </button></td>
            <?php
                        
              }elseif($row["order_status"]=="processing"){
                            
            ?>
                <td><button class="btn btn-warning" onclick="ChangeOrderStatus('<?=$row['order_id']?>')">Processing </button></td>
            <?php
                        
              }elseif($row["order_status"]=="shipped"){
                            
            ?>
                <td><button class="btn btn-secondary" onclick="ChangeOrderStatus('<?=$row['order_id']?>')">Shipped </button></td>
            <?php
                            
              }elseif($row["order_status"]=="delivered"){
            ?>
                <td><button class="btn btn-success" onclick="ChangeOrderStatus('<?=$row['order_id']?>')">Delivered </button></td>
            <?php
                        
              }
              elseif($row["order_status"]=="cancelled"){
            ?>
                <td><button class="btn btn-danger" onclick="ChangeOrderStatus('<?=$row['order_id']?>')">Cancelled</button></td>
        
            <?php
            }if($row["payment_status"]=="pending"){
            ?>
                <td><button class="btn btn-warning"  onclick="ChangePay('<?=$row['order_id']?>')">Pending</button></td>
            <?php
                        
            }if($row["payment_status"]=="completed"){
            ?>
                <td><button class="btn btn-success"  onclick="ChangePay('<?=$row['order_id']?>')">Completed</button></td>
            <?php
                        
            }
            else if($row["payment_status"]=="failed"){
            ?>
                <td><button class="btn btn-danger" onclick="ChangePay('<?=$row['order_id']?>')">Failed </button></td>
            <?php
                }
            ?>
              
        <td><a class="btn btn-primary openPopup" data-href="./adminView/viewEachOrder.php?orderID=<?=$row['order_id']?>" href="javascript:void(0);">View</a></td>
        </tr>
    <?php
            
        }
      }
    ?>
     
  </table>
   
</div>
<!-- Modal -->
<div class="modal fade" id="viewModal" role="dialog">
    <div class="modal-dialog modal-lg">
      <!-- Modal content-->
      <div class="modal-content">
        <div class="modal-header">
          
          <h4 class="modal-title">Order Details</h4>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="order-view-modal modal-body">
        
        </div>
      </div><!--/ Modal content-->
    </div><!-- /Modal dialog-->
  </div>
<script>
     //for view order modal  
    $(document).ready(function(){
      $('.openPopup').on('click',function(){
        var dataURL = $(this).attr('data-href');
    
        $('.order-view-modal').load(dataURL,function(){
          $('#viewModal').modal({show:true});
        });
      });
    });
 </script>