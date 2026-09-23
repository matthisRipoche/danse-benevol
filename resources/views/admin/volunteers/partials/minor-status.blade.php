@if ($volunteer->minor_validated_at)
    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold whitespace-nowrap text-emerald-800">
        Mineur · validé le {{ $volunteer->minor_validated_at->format('d/m/Y') }}
    </span>
@else
    <form method="POST" action="{{ route('admin.volunteers.validate-minor', $volunteer) }}" class="inline-flex items-center gap-1.5">
        @csrf
        <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold whitespace-nowrap text-amber-800">Mineur · à valider</span>
        <button type="submit" class="rounded-full bg-brand-500 px-2.5 py-0.5 text-xs font-semibold text-white hover:bg-brand-600">
            Valider
        </button>
    </form>
@endif
