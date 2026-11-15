import { Head, Link, router } from '@inertiajs/react';
import { useEffect } from 'react';

type Ticket = {
    number: string;
    status: 'waiting' | 'serving' | 'completed' | 'skipped' | 'cancelled';
    departmentId: number;
    department: string;
    service: string;
    date: string;
};

type Props = {
    ticket: Ticket;
    ahead: number;
    currentlyServing: string | null;
};

const statusLabels: Record<Ticket['status'], string> = {
    waiting: 'Waiting',
    serving: 'Now serving',
    completed: 'Completed',
    skipped: 'Skipped',
    cancelled: 'Cancelled',
};

export default function TicketPage({ ticket, ahead, currentlyServing }: Props) {
    useEffect(() => {
        if (
            ticket.status === 'completed' ||
            ticket.status === 'cancelled' ||
            ticket.status === 'skipped'
        )
            return;

        const timer = window.setInterval(() => {
            router.reload({
                only: ['ticket', 'ahead', 'currentlyServing'],
            });
        }, 5000);

        return () => window.clearInterval(timer);
    }, [ticket.status]);

    return (
        <>
            <Head title={`Ticket ${ticket.number}`} />
            <main className="flex min-h-screen items-center justify-center bg-slate-50 px-5 py-10 text-slate-900">
                <section className="w-full max-w-lg rounded-3xl border border-slate-200 bg-white p-8 text-center shadow-xl shadow-slate-200/60">
                    <Link
                        href="/"
                        className="text-sm font-bold tracking-tight text-slate-500"
                    >
                        Campus<span className="text-blue-600">Q</span>
                    </Link>
                    <p className="mt-7 text-sm font-semibold tracking-[0.18em] text-blue-700 uppercase">
                        Your queue ticket
                    </p>
                    <h1 className="mt-2 text-6xl font-bold tracking-tight">
                        {ticket.number}
                    </h1>
                    <div className="mt-5 inline-flex rounded-full bg-blue-50 px-4 py-1.5 text-sm font-semibold text-blue-800">
                        {statusLabels[ticket.status]}
                    </div>
                    <div className="mt-8 grid grid-cols-2 gap-3 text-left">
                        <div className="rounded-2xl bg-slate-50 p-4">
                            <p className="text-xs tracking-wide text-slate-500 uppercase">
                                Department
                            </p>
                            <p className="mt-1 font-semibold">
                                {ticket.department}
                            </p>
                        </div>
                        <div className="rounded-2xl bg-slate-50 p-4">
                            <p className="text-xs tracking-wide text-slate-500 uppercase">
                                Service
                            </p>
                            <p className="mt-1 font-semibold">
                                {ticket.service}
                            </p>
                        </div>
                    </div>
                    <div className="mt-4 rounded-2xl border border-slate-200 p-5">
                        <p className="text-sm text-slate-500">
                            Currently serving
                        </p>
                        <p className="mt-1 text-2xl font-semibold">
                            {currentlyServing ?? '—'}
                        </p>
                    </div>
                    <Link
                        href={`/display/${ticket.departmentId}`}
                        className="mt-5 inline-block text-sm font-semibold text-blue-700 hover:text-blue-900"
                    >
                        View {ticket.department} live queue
                    </Link>
                    {ticket.status === 'waiting' && (
                        <p className="mt-5 text-sm text-slate-600">
                            {ahead === 0
                                ? 'You’re next in line.'
                                : `${ahead} ${ahead === 1 ? 'person' : 'people'} ahead of you.`}
                        </p>
                    )}
                    <p className="mt-7 text-xs text-slate-400">
                        This page updates automatically every 5 seconds.
                    </p>
                </section>
            </main>
        </>
    );
}
