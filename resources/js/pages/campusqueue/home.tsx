import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

type Service = {
    id: number;
    name: string;
};

type Department = {
    id: number;
    name: string;
    services: Service[];
};

type Props = {
    departments: Department[];
};

export default function Home({ departments }: Props) {
    const [departmentId, setDepartmentId] = useState('');
    const [serviceId, setServiceId] = useState('');
    const [joining, setJoining] = useState(false);
    const department = departments.find(
        (item) => item.id === Number(departmentId),
    );

    function joinQueue(event: React.FormEvent<HTMLFormElement>) {
        event.preventDefault();

        if (!serviceId) return;

        setJoining(true);
        router.post(
            `/services/${serviceId}/tickets`,
            {},
            { onFinish: () => setJoining(false) },
        );
    }

    return (
        <>
            <Head title="CampusQ | Skip the line" />
            <main className="min-h-screen bg-slate-50 text-slate-900">
                <header className="mx-auto flex max-w-6xl items-center justify-between px-6 py-6">
                    <Link href="/" className="text-xl font-bold tracking-tight">
                        Campus<span className="text-blue-600">Q</span>
                    </Link>
                    <nav className="flex items-center gap-5 text-sm">
                        <Link
                            href="/departments/register"
                            className="text-slate-600 hover:text-blue-700"
                        >
                            Register a department
                        </Link>
                        <Link
                            href="/login"
                            className="font-medium text-blue-700 hover:text-blue-900"
                        >
                            Staff login
                        </Link>
                    </nav>
                </header>

                <section className="mx-auto grid max-w-6xl gap-12 px-6 pt-10 pb-20 md:grid-cols-[1.1fr_0.9fr] md:items-center md:pt-20">
                    <div>
                        <p className="mb-4 text-sm font-semibold tracking-[0.2em] text-blue-700 uppercase">
                            Campus queue, made simple
                        </p>
                        <h1 className="max-w-xl text-5xl leading-tight font-semibold tracking-tight md:text-6xl">
                            Your place in line, without the line.
                        </h1>
                        <p className="mt-6 max-w-lg text-lg leading-8 text-slate-600">
                            Join a campus service queue from your phone, then
                            keep an eye on your place while you wait.
                        </p>
                        <div className="mt-9 flex flex-wrap gap-3 text-sm text-slate-600">
                            <span className="rounded-full bg-white px-4 py-2 shadow-sm">
                                No account needed
                            </span>
                            <span className="rounded-full bg-white px-4 py-2 shadow-sm">
                                Live ticket status
                            </span>
                        </div>
                    </div>

                    <div className="rounded-3xl border border-slate-200 bg-white p-7 shadow-xl shadow-slate-200/60 md:p-9">
                        <div className="mb-7">
                            <p className="text-sm font-semibold text-blue-700">
                                START HERE
                            </p>
                            <h2 className="mt-2 text-2xl font-semibold">
                                Join a queue
                            </h2>
                            <p className="mt-2 text-sm text-slate-500">
                                Choose where you need help. We’ll save your
                                ticket.
                            </p>
                        </div>
                        {departments.length === 0 ? (
                            <div className="rounded-2xl bg-slate-50 p-5 text-sm leading-6 text-slate-600">
                                No departments are accepting tickets yet. Please
                                check back soon.
                            </div>
                        ) : (
                            <form onSubmit={joinQueue} className="space-y-5">
                                <label className="block text-sm font-medium">
                                    Department
                                    <select
                                        required
                                        value={departmentId}
                                        onChange={(event) => {
                                            setDepartmentId(event.target.value);
                                            setServiceId('');
                                        }}
                                        className="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-base outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-100"
                                    >
                                        <option value="">
                                            Select a department
                                        </option>
                                        {departments.map((item) => (
                                            <option
                                                key={item.id}
                                                value={item.id}
                                            >
                                                {item.name}
                                            </option>
                                        ))}
                                    </select>
                                </label>
                                <label className="block text-sm font-medium">
                                    Service
                                    <select
                                        required
                                        disabled={
                                            !department ||
                                            department.services.length === 0
                                        }
                                        value={serviceId}
                                        onChange={(event) =>
                                            setServiceId(event.target.value)
                                        }
                                        className="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-base outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-100 disabled:bg-slate-100"
                                    >
                                        <option value="">
                                            Select a service
                                        </option>
                                        {department?.services.map((service) => (
                                            <option
                                                key={service.id}
                                                value={service.id}
                                            >
                                                {service.name}
                                            </option>
                                        ))}
                                    </select>
                                    {department &&
                                        department.services.length === 0 && (
                                            <span className="mt-2 block text-xs text-slate-500">
                                                This department has no active
                                                services yet.
                                            </span>
                                        )}
                                </label>
                                <button
                                    type="submit"
                                    disabled={!serviceId || joining}
                                    className="w-full rounded-xl bg-blue-700 px-5 py-3.5 font-semibold text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    {joining
                                        ? 'Getting your ticket…'
                                        : 'Get my queue number'}
                                </button>
                            </form>
                        )}
                        <p className="mt-5 text-center text-xs text-slate-500">
                            Keep your ticket link to check your queue status
                            later.
                        </p>
                    </div>
                </section>
            </main>
        </>
    );
}
