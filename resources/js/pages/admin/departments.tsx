import { Head, router } from '@inertiajs/react';

type Department = {
    id: number;
    name: string;
    email: string;
    status: 'pending' | 'approved' | 'inactive';
};

type Props = {
    departments: Department[];
};

export default function Departments({ departments }: Props) {
    const pending = departments.filter(
        (department) => department.status === 'pending',
    );
    const active = departments.filter(
        (department) => department.status !== 'pending',
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
                    {active.length === 0 ? (
                        <p className="rounded-xl border bg-card p-6 text-sm text-muted-foreground">
                            No approved departments yet.
                        </p>
                    ) : (
                        <div className="overflow-hidden rounded-xl border bg-card">
                            {active.map((department) => (
                                <div
                                    key={department.id}
                                    className="flex items-center justify-between border-b p-4 last:border-b-0"
                                >
                                    <div>
                                        <p className="font-medium">
                                            {department.name}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {department.email}
                                        </p>
                                    </div>
                                    <span className="rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-800 capitalize">
                                        {department.status}
                                    </span>
                                </div>
                            ))}
                        </div>
                    )}
                </section>
            </div>
        </>
    );
}
