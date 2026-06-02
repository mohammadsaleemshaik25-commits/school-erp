<div class="card shadow-sm">
	<div class="table-responsive">
		<table class="table table-striped table-sm align-middle mb-0">
			<thead class="table-light"><tr><th>Date</th><th>Admission No.</th><th>Student</th><th>Collector</th><th class="text-end">Books</th><th class="text-end">Tuition</th><th class="text-end">Total</th></tr></thead>
			<tbody>
				@forelse($rows as $row)
					<tr>
						<td>{{ $row->payment_date }}</td>
						<td>{{ $row->admission_no }}</td>
						<td>{{ $row->student_name }}</td>
						<td>{{ $row->collector_name }}</td>
						<td class="text-end">{{ number_format($row->books_fee_paid, 2) }}</td>
						<td class="text-end">{{ number_format($row->tuition_fee_paid, 2) }}</td>
						<td class="text-end">{{ number_format($row->amount, 2) }}</td>
					</tr>
				@empty
					<tr><td colspan="7" class="text-center text-muted">No collections found.</td></tr>
				@endforelse
			</tbody>
		</table>
	</div>
</div>
