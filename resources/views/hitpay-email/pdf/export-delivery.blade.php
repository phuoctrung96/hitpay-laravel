<style>
    table, th, td{
        border: 1px solid black;
        border-collapse: collapse;
        word-break:break-all;
        word-wrap:break-word;
    }
    table {
        width: 100%;
        table-layout:fixed;
    }
</style>
<table>
    <tr>
        <th>#</th>
        <th>ID</th>
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
        <th>Pickup</th>
        <th>Ordered Date</th>
        <th>Slot Date</th>
        <th>Slot Time</th>
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
            <td>{{$order[16]}}</td>
        </tr>
    @endforeach
</table>
