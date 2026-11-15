import { Head, router } from '@inertiajs/react';
import { useEffect } from 'react';

type Ticket = {
    ticketNumber: string;
    status: 'waiting' | 'serving' | 'completed';
};

type Service = {
    id: number;
    name: string;
    code: string;
    location: string | null;
    isPaused: boolean;
    pausedUntil: string | null;
    batchNumber: number | null;
    tickets: Ticket[];
};

type Props = {
    department: {
        name: string;
    };
    services: Service[];
};

export default function Display({ department, services }: Props) {
    useEffect(() => {
        const timer = window.setInterval(() => {
            router.reload({ only: ['department', 'services'] });
        }, 5000);

        return () => window.clearInterval(timer);
    }, []);

    return (
        <>
            <Head title={`${department.name} queue display`} />
            <main className="min-h-screen bg-slate-950 p-6 text-white md:p-10">
                <header className="mb-8 flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p className="text-sm font-semibold tracking-[0.18em] text-sky-300 uppercase">
                            Queue display
                        </p>
                        <h1 className="mt-2 text-4xl font-bold tracking-tight md:text-5xl">
                            {department.name}
                        </h1>
                    </div>
                    <p className="text-sm text-slate-400">
                        Updates automatically
                    </p>
                </header>

                {services.length === 0 ? (
                    <p className="rounded-2xl border border-white/10 bg-white/5 p-8 text-center text-xl text-slate-300">
                        No active services.
                    </p>
                ) : (
                    <div className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                        {services.map((service) => (
                            <section
                                key={service.id}
                                className="rounded-2xl border border-white/10 bg-slate-900 p-6"
                            >
                                <header className="flex items-start justify-between gap-4">
                                    <h2 className="text-2xl font-semibold">
                                        {service.name}
                                    </h2>
                                    <span className="rounded-lg bg-white/10 px-3 py-1 text-sm font-semibold text-slate-300">
                                        {service.code}
                                    </span>
                                </header>
                                {service.location && (
                                    <p className="mt-2 text-sm font-medium text-sky-300">
                                        {service.location}
                                    </p>
                                )}

                                {service.isPaused ? (
                                    <div className="mt-8 rounded-xl border border-amber-300/20 bg-amber-300/10 p-6 text-center">
                                        <p className="text-xl font-semibold text-amber-200">
                                            Service unavailable
                                        </p>
                                        <p className="mt-2 text-slate-300">
                                            We&apos;ll be back at{' '}
                                            {new Date(
                                                service.pausedUntil ?? '',
                                            ).toLocaleTimeString([], {
                                                hour: 'numeric',
                                                minute: '2-digit',
                                            })}
                                            .
                                        </p>
                                    </div>
                                ) : service.batchNumber === null ? (
                                    <p className="mt-8 rounded-xl bg-white/5 p-6 text-center text-lg text-slate-400">
                                        No tickets waiting
                                    </p>
                                ) : (
                                    <>
                                        <p className="mt-5 text-sm font-medium text-slate-400">
                                            Group {service.batchNumber} · Up to
                                            5 tickets
                                        </p>
                                        <div className="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                            {service.tickets.map((ticket) => (
                                                <div
                                                    key={ticket.ticketNumber}
                                                    className={`rounded-xl border p-4 ${
                                                        ticket.status ===
                                                        'serving'
                                                            ? 'border-emerald-400 bg-emerald-400/15'
                                                            : ticket.status ===
                                                                'completed'
                                                              ? 'border-white/10 bg-white/5 text-slate-500'
                                                              : 'border-sky-400/30 bg-sky-400/10'
                                                    }`}
                                                >
                                                    <p className="text-2xl font-bold tracking-wide md:text-3xl">
                                                        {ticket.ticketNumber}
                                                    </p>
                                                    <p className="mt-1 text-xs font-semibold tracking-wide uppercase">
                                                        {ticket.status ===
                                                        'serving'
                                                            ? 'Now serving'
                                                            : ticket.status}
                                                    </p>
                                                </div>
                                            ))}
                                        </div>
                                    </>
                                )}
                            </section>
                        ))}
                    </div>
                )}
            </main>
        </>
    );
}
