<h1>ini halaman cashier</h1>

<form method="POST" action="{{ route('logout') }}" style="display: inline;">
    @csrf
    <button type="submit" class="btn btn-danger">Logout</button>
</form>