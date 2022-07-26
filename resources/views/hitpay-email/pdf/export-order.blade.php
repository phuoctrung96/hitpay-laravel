<style>
    table, th{
        font-size: 10px;
    }
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
    <th>Charge ID</th>
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
    <th>Variants</th>
    <th>Buyer remarks</th>
    <th>Ordered Date</th>
    <th>Completed Date</th>

  </tr>
    @php $incrementNumber=1; @endphp
    @foreach($data as $order)
        <tr>
            <td>{{ $incrementNumber }}</td>
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
            <td>{{$order[17]}}</td>
        </tr>
        @php $incrementNumber++ @endphp
    @endforeach
</table>
