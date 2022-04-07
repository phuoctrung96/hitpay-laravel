<style>
    table, th, td{
        border: 1px solid black;
    }
    table{
        border-collapse: collapse;
    }
</style>
<table border="0" cellpadding="0" cellspacing="0"
       style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;" width="100%">
    <tr>
    <th>#</th>
    <th>ID</th>
    <th>Reference</th>
    <th>Customer Name</th>
    <th>Customer Email</th>
    <th>Customer Phone Number</th>
    <th>Customer Address</th>
    <th>Currency</th>
    <th>Amount</th>
    <th>Discount Name</th>
    <th>Discount Amount</th>
    <th>Status</th>
    <th>Products</th>
        <th>Buyer remarks</th>
    <th>Ordered Date</th>
    <th>Completed Date</th>

  </tr>
    @foreach($data as $order)
        <tr>
            <td>{{$order[0]}}</td>
            <td>{{$order[1]}}</td>
            <td>{{$order[2]}}</td>
            <td>{{$order[3]}}</td>
            <td>{{$order[4]}}</td>
            <td>{{$order[5]}}</td>
            <td>{{$order[6]}}</td>
            <td>{{$order[7]}}</td>
            <td>{{$order[8]}}</td>
            <td>{{$order[9]}}</td>
            <td>{{$order[10]}}</td>
            <td>{{$order[11]}}</td>
            <td>{{$order[12]}}</td>
            <td>{{$order[13]}}</td>
            <td>{{$order[14]}}</td>
            <td>{{$order[15]}}</td>
        </tr>
    @endforeach
</table>