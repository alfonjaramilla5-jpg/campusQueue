import { Head, router, useForm } from '@inertiajs/react';

type Service = {
    id: number;
    name: string;
    code: string;
    location: string | null;
    isActive: boolean;
};

type Department = {
    id: number;
    name: string;
    email: string;
    status: 'pending' | 'approved' | 'inactive';
    services: Service[];
};

type Props = {
    departments: Department[];
};

export default function Departments({ departments }: Props) {
    const pending = departments.filter(
        (department) => department.status === 'pending',
    );
    const approved = departments.filter(
        (department) => department.status === 'approved',
    );
    const inactive = departments.filter(
        (department) => department.status === 'inactive',
    );

    return (
        <>
            <Head title="Department approvals" />
            <div className="space-y-8 p-4 md:p-8">
                <div>
                    <p className="text-sm font-semibold tracking-[0.16em] text-blue-700 uppercase">
                        Administration
                    </p>
                    <h1 className="mt-2 text-3xl font-semibold tracking-tight">
                        Department applications
                    </h1>
                    <p className="mt-2 text-sm text-muted-foreground">
                        Review applications before departments can accept queue
                        tickets.
                    </p>
                </div>
                <section className="space-y-4">
                    <h2 className="text-lg font-semibold">
                        Waiting for approval{' '}
                        <span className="text-sm font-normal text-muted-foreground">
                            ({pending.length})
                        </span>
                    </h2>
                    {pending.length === 0 ? (
                        <p className="rounded-xl border bg-card p-6 text-sm text-muted-foreground">
                            No pending applications.
                        </p>
                    ) : (
                        pending.map((department) => (
                            <article
                                key={department.id}
                                className="flex flex-col justify-between gap-5 rounded-xl border bg-card p-5 md:flex-row md:items-center"
                            >
                                <div>
                                    <h3 className="font-semibold">
                                        {department.name}
                                    </h3>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        {department.email}
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    onClick={() =>
                                        router.post(
                                            `/admin/departments/${department.id}/approve`,
                                        )
                                    }
                                    className="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800"
                                >
                                    Approve department
                                </button>
                            </article>
                        ))
                    )}
                </section>
                <section className="space-y-4">
                    <h2 className="text-lg font-semibold">
                        Approved departments
                    </h2>
                    {approved.length === 0 ? (
                        <p className="rounded-xl border bg-card p-6 text-sm text-muted-foreground">
                            No approved departments yet.
                        </p>
                    ) : (
                        <div className="overflow-hidden rounded-xl border bg-card">
                            {approved.map((department) => (
                                <article
                                    key={department.id}
                                    className="space-y-5 border-b p-5 last:border-b-0"
                                >
                                    <div className="flex flex-wrap items-center justify-between gap-3">
                                        <div>
                                            <p className="font-medium">
                                                {department.name}
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                {department.email}
                                            </p>
                                        </div>
                                        <span className="rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-800">
                                            Approved
                                        </span>
                                    </div>
                                    <section className="space-y-3">
                                        <h3 className="font-semibold">
                                            Services
                                        </h3>
                                        {department.services.length === 0 ? (
                                            <p className="text-sm text-muted-foreground">
                                                No services yet.
                                            </p>
                                        ) : (
                                            <div className="space-y-3">
                                                {department.services.map(
                                                    (service) => (
                                                        <ServiceEditor
                                                            key={service.id}
                                                            service={service}
                                                        />
                                                    ),
                                                )}
                                            </div>
                                        )}
                                        {department.status === 'approved' && (
                                            <AddServiceForm
                                                departmentId={department.id}
                                            />
                                        )}
                                    </section>
                                </article>
                            ))}
                        </div>
                    )}
                </section>
                <section className="space-y-4">
                    <h2 className="text-lg font-semibold">
                        Inactive departments
                    </h2>
                    {inactive.length === 0 ? (
                        <p className="rounded-xl border bg-card p-6 text-sm text-muted-foreground">
                            No inactive departments.
                        </p>
                    ) : (
                        <div className="overflow-hidden rounded-xl border bg-card">
                            {inactive.map((department) => (
                                <article
                                    key={department.id}
                                    className="space-y-5 border-b p-5 last:border-b-0"
                                >
                                    <div className="flex flex-wrap items-center justify-between gap-3">
                                        <div>
                                            <p className="font-medium">
                                                {department.name}
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                {department.email}
                                            </p>
                                        </div>
                                        <span className="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">
                                            Inactive
                                        </span>
                                    </div>
                                    <section className="space-y-3">
                                        <h3 className="font-semibold">
                                            Services
                                        </h3>
                                        {department.services.length === 0 ? (
                                            <p className="text-sm text-muted-foreground">
                                                No services yet.
                                            </p>
                                        ) : (
                                            <div className="space-y-3">
                                                {department.services.map(
                                                    (service) => (
                                                        <ServiceEditor
                                                            key={service.id}
                                                            service={service}
                                                        />
                                                    ),
                                                )}
                                            </div>
                                        )}
                                    </section>
                                </article>
                            ))}
                        </div>
                    )}
                </section>
            </div>
        </>
    );
}

function AddServiceForm({ departmentId }: { departmentId: number }) {
    const form = useForm({ name: '', location: '' });

    return (
        <form
            onSubmit={(event) => {
                event.preventDefault();
                form.post(`/admin/departments/${departmentId}/services`, {
                    preserveScroll: true,
                    onSuccess: () => form.reset(),
                });
            }}
            className="flex flex-col gap-2 sm:flex-row"
        >
            <div className="flex-1">
                <input
                    required
                    maxLength={255}
                    aria-label="New service name"
                    placeholder="New service name"
                    value={form.data.name}
                    onChange={(event) =>
                        form.setData('name', event.target.value)
                    }
                    className="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm"
                />
                {form.errors.name && (
                    <p className="mt-1 text-sm text-red-600">
                        {form.errors.name}
                    </p>
                )}
            </div>
            <div className="flex-1">
                <input
                    maxLength={255}
                    aria-label="Window or location"
                    placeholder="Window or location"
                    value={form.data.location}
                    onChange={(event) =>
                        form.setData('location', event.target.value)
                    }
                    className="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm"
                />
                {form.errors.location && (
                    <p className="mt-1 text-sm text-red-600">
                        {form.errors.location}
                    </p>
                )}
            </div>
            <button
                type="submit"
                disabled={form.processing}
                className="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800 disabled:opacity-50"
            >
                Add service
            </button>
        </form>
    );
}

function ServiceEditor({ service }: { service: Service }) {
    const form = useForm({
        name: service.name,
        location: service.location ?? '',
    });

    return (
        <form
            onSubmit={(event) => {
                event.preventDefault();
                form.put(`/admin/services/${service.id}`, {
                    preserveScroll: true,
                });
            }}
            className="space-y-3 rounded-lg border p-4"
        >
            <div className="flex flex-wrap items-center justify-between gap-2">
                <p className="text-sm font-medium">
                    {service.code} queue ·{' '}
                    {service.isActive ? 'Active' : 'Inactive'}
                </p>
                {service.isActive ? (
                    <button
                        type="button"
                        disabled={form.processing}
                        onClick={() => {
                            if (
                                window.confirm(
                                    'Deactivate this service? Existing tickets will be preserved.',
                                )
                            ) {
                                form.delete(
                                    `/admin/services/${service.id}`,
                                    { preserveScroll: true },
                                );
                            }
                        }}
                        className="text-sm font-medium text-red-700 hover:text-red-900 disabled:opacity-50"
                    >
                        Deactivate
                    </button>
                ) : (
                    <button
                        type="button"
                        disabled={form.processing}
                        onClick={() =>
                            form.patch(
                                `/admin/services/${service.id}/restore`,
                                { preserveScroll: true },
                            )
                        }
                        className="text-sm font-medium text-blue-700 hover:text-blue-900 disabled:opacity-50"
                    >
                        Activate
                    </button>
                )}
            </div>
            <div className="flex flex-col gap-2 sm:flex-row">
                <div className="flex-1">
                    <input
                        required
                        maxLength={255}
                        aria-label={`Service name for ${service.code}`}
                        value={form.data.name}
                        onChange={(event) =>
                            form.setData('name', event.target.value)
                        }
                        className="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm"
                    />
                    {form.errors.name && (
                        <p className="mt-1 text-sm text-red-600">
                            {form.errors.name}
                        </p>
                    )}
                </div>
                <div className="flex-1">
                    <input
                        maxLength={255}
                        aria-label={`Window or location for ${service.code}`}
                        placeholder="Window or location"
                        value={form.data.location}
                        onChange={(event) =>
                            form.setData('location', event.target.value)
                        }
                        className="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm"
                    />
                    {form.errors.location && (
                        <p className="mt-1 text-sm text-red-600">
                            {form.errors.location}
                        </p>
                    )}
                </div>
                <button
                    type="submit"
                    disabled={form.processing}
                    className="rounded-lg border px-4 py-2 text-sm font-semibold hover:bg-muted disabled:opacity-50"
                >
                    Save changes
                </button>
            </div>
            {form.errors.service && (
                <p role="alert" className="text-sm text-red-600">
                    {form.errors.service}
                </p>
            )}
        </form>
    );
}
