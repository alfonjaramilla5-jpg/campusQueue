import { Head, router, useForm } from '@inertiajs/react';
import { useEffect } from 'react';

type Ticket = {
    id: number;
    ticket_number: string;
};

type Service = {
    id: number;
    name: string;
    code: string;
    location: string | null;
    isActive: boolean;
    isPaused: boolean;
    pausedUntil: string | null;
    waitingCount: number;
    waiting: Ticket[];
    serving: Ticket | null;
};

type Department = {
    id: number;
    name: string;
    status: 'pending' | 'approved' | 'inactive';
};

type Props = {
    department: Department;
    services: Service[];
    flash?: { success?: string };
};

export default function Dashboard({ department, services, flash }: Props) {
    const form = useForm({ name: '', location: '' });

    useEffect(() => {
        if (department.status !== 'approved') return;

        const timer = window.setInterval(() => {
            router.reload({ only: ['department', 'services'] });
        }, 5000);

        return () => window.clearInterval(timer);
    }, [department.status]);

    return (
        <>
            <Head title="Queue desk" />
            <div className="space-y-7 p-4 md:p-8">
                <header className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <p className="text-sm font-semibold tracking-[0.16em] text-blue-700 uppercase">
                            Department queue desk
                        </p>
                        <h1 className="mt-2 text-3xl font-semibold tracking-tight">
                            {department.name}
                        </h1>
                        <p className="mt-2 text-sm text-muted-foreground">
                            {department.status === 'approved'
                                ? 'Tickets update automatically while this page is open.'
                                : 'Your application is being reviewed.'}
                        </p>
                    </div>
                    {department.status === 'approved' && (
                        <div className="flex flex-wrap gap-4">
                            <a
                                href={`/display/${department.id}`}
                                className="text-sm font-medium text-blue-700 hover:text-blue-900"
                            >
                                Open queue display
                            </a>
                            <a
                                href="/"
                                className="text-sm font-medium text-blue-700 hover:text-blue-900"
                            >
                                View public queue page
                            </a>
                        </div>
                    )}
                </header>

                {flash?.success && (
                    <div
                        role="status"
                        className="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
                    >
                        {flash.success}
                    </div>
                )}

                {department.status !== 'approved' ? (
                    <section className="rounded-xl border bg-card p-6">
                        <h2 className="font-semibold">
                            Pending administrator approval
                        </h2>
                        <p className="mt-2 text-sm text-muted-foreground">
                            You can sign in, but services and queues will be
                            available once your department is approved.
                        </p>
                    </section>
                ) : (
                    <>
                        <section className="rounded-xl border bg-card p-5">
                            <h2 className="font-semibold">Add a service</h2>
                            <form
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    form.post('/services', {
                                        onSuccess: () => form.reset(),
                                    });
                                }}
                                className="mt-4 flex flex-col gap-3 sm:flex-row"
                            >
                                <div className="flex-1">
                                    <input
                                        required
                                        maxLength={255}
                                        placeholder="Service name"
                                        aria-label="Service name"
                                        value={form.data.name}
                                        onChange={(event) =>
                                            form.setData(
                                                'name',
                                                event.target.value,
                                            )
                                        }
                                        className="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-100"
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
                                        placeholder="Window or location (optional)"
                                        aria-label="Window or location (optional)"
                                        value={form.data.location}
                                        onChange={(event) =>
                                            form.setData(
                                                'location',
                                                event.target.value,
                                            )
                                        }
                                        className="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-100"
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
                                    className="rounded-lg bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-800 disabled:opacity-50"
                                >
                                    {form.processing
                                        ? 'Adding…'
                                        : 'Add service'}
                                </button>
                            </form>
                        </section>

                        <section className="space-y-4">
                            <div>
                                <h2 className="text-xl font-semibold">
                                    Services
                                </h2>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Call the oldest waiting ticket, then
                                    complete it before calling another.
                                </p>
                            </div>
                            {services.length === 0 ? (
                                <p className="rounded-xl border bg-card p-6 text-sm text-muted-foreground">
                                    No services yet. Add one above to start
                                    accepting tickets.
                                </p>
                            ) : (
                                <div className="grid gap-4 lg:grid-cols-2">
                                    {services.map((service) => (
                                        <article
                                            key={service.id}
                                            className="rounded-xl border bg-card p-5"
                                        >
                                            <div className="flex items-start justify-between gap-4">
                                                <div>
                                                    <h3 className="font-semibold">
                                                        {service.name}
                                                    </h3>
                                                    <p className="mt-1 text-sm text-muted-foreground">
                                                        {service.code} queue
                                                    </p>
                                                    {service.location && (
                                                        <p className="mt-1 text-sm font-medium text-muted-foreground">
                                                            {service.location}
                                                        </p>
                                                    )}
                                                </div>
                                                <div className="rounded-lg bg-slate-100 px-3 py-2 text-center">
                                                    <p className="text-xl font-semibold">
                                                        {service.waitingCount}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        waiting
                                                    </p>
                                                </div>
                                            </div>
                                            <div className="mt-3 flex flex-wrap items-center justify-between gap-2">
                                                <p className="text-sm text-muted-foreground">
                                                    {service.isPaused
                                                        ? `Paused until ${new Date(service.pausedUntil ?? '').toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })}`
                                                        : service.isActive
                                                          ? 'Service is open'
                                                          : 'Service is inactive'}
                                                </p>
                                                {service.isActive && (
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            router.post(
                                                                `/staff/services/${service.id}/${service.isPaused ? 'resume' : 'pause'}`,
                                                            )
                                                        }
                                                        className={`rounded-lg px-3 py-1.5 text-sm font-semibold text-white ${
                                                            service.isPaused
                                                                ? 'bg-emerald-700 hover:bg-emerald-800'
                                                                : 'bg-amber-600 hover:bg-amber-700'
                                                        }`}
                                                    >
                                                        {service.isPaused
                                                            ? 'Resume service'
                                                            : 'Pause for 1 hour'}
                                                    </button>
                                                )}
                                            </div>

                                            <div className="mt-5 rounded-lg border border-dashed p-4 text-center">
                                                <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                                    Currently serving
                                                </p>
                                                {service.serving ? (
                                                    <>
                                                        <p className="mt-1 text-2xl font-bold">
                                                            {
                                                                service.serving
                                                                    .ticket_number
                                                            }
                                                        </p>
                                                        <div className="mt-3 flex flex-wrap justify-center gap-2">
                                                            <button
                                                                type="button"
                                                                onClick={() =>
                                                                    router.post(
                                                                        `/staff/tickets/${service.serving?.id}/complete`,
                                                                    )
                                                                }
                                                                className="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800"
                                                            >
                                                                Complete ticket
                                                            </button>
                                                            <button
                                                                type="button"
                                                                onClick={() => {
                                                                    if (
                                                                        window.confirm(
                                                                                    service.isPaused
                                                                                        ? 'Skip this ticket? The waiting queue will remain paused.'
                                                                                        : 'Skip this ticket and call the next waiting student?',
                                                                        )
                                                                    ) {
                                                                        router.post(
                                                                            `/staff/tickets/${service.serving?.id}/skip`,
                                                                        );
                                                                    }
                                                                }}
                                                                className="rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700"
                                                            >
                                                                {service.isPaused
                                                                    ? 'Skip ticket'
                                                                    : 'Skip & call next'}
                                                            </button>
                                                        </div>
                                                    </>
                                                ) : (
                                                    <p className="mt-1 text-lg text-muted-foreground">
                                                        No active ticket
                                                    </p>
                                                )}
                                            </div>

                                            <div className="mt-4">
                                                <p className="text-sm font-medium">
                                                    Next waiting
                                                </p>
                                                {service.waiting.length ? (
                                                    <div className="mt-2 flex flex-wrap gap-2">
                                                        {service.waiting.map(
                                                            (ticket) => (
                                                                <span
                                                                    key={
                                                                        ticket.id
                                                                    }
                                                                    className="rounded-md bg-slate-100 px-2.5 py-1 text-sm font-medium"
                                                                >
                                                                    {
                                                                        ticket.ticket_number
                                                                    }
                                                                </span>
                                                            ),
                                                        )}
                                                    </div>
                                                ) : (
                                                    <p className="mt-2 text-sm text-muted-foreground">
                                                        No one is waiting.
                                                    </p>
                                                )}
                                            </div>

                                            <button
                                                type="button"
                                                disabled={
                                                    Boolean(service.serving) ||
                                                    service.waitingCount === 0 ||
                                                    service.isPaused ||
                                                    !service.isActive
                                                }
                                                onClick={() =>
                                                    router.post(
                                                        `/staff/services/${service.id}/call-next`,
                                                    )
                                                }
                                                className="mt-5 w-full rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-50"
                                            >
                                                Call next
                                            </button>
                                        </article>
                                    ))}
                                </div>
                            )}
                        </section>
                    </>
                )}
            </div>
        </>
    );
}
