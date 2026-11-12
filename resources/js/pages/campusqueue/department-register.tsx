import { Head, Link, useForm } from '@inertiajs/react';

type Registration = {
    department_name: string;
    department_email: string;
    password: string;
    password_confirmation: string;
};

const initialValues: Registration = {
    department_name: '',
    department_email: '',
    password: '',
    password_confirmation: '',
};

export default function DepartmentRegister() {
    const { data, setData, post, processing, errors } = useForm(initialValues);
    const fields: {
        key: keyof Registration;
        label: string;
        type: string;
        autoComplete: string;
    }[] = [
        {
            key: 'department_name',
            label: 'Department name',
            type: 'text',
            autoComplete: 'organization',
        },
        {
            key: 'department_email',
            label: 'Department email',
            type: 'email',
            autoComplete: 'email',
        },
        {
            key: 'password',
            label: 'Password',
            type: 'password',
            autoComplete: 'new-password',
        },
        {
            key: 'password_confirmation',
            label: 'Confirm password',
            type: 'password',
            autoComplete: 'new-password',
        },
    ];

    return (
        <>
            <Head title="Register a department" />
            <main className="min-h-screen bg-slate-50 px-6 py-10 text-slate-900">
                <div className="mx-auto max-w-xl">
                    <Link href="/" className="text-xl font-bold tracking-tight">
                        Campus<span className="text-blue-600">Q</span>
                    </Link>
                    <div className="mt-8 rounded-3xl border border-slate-200 bg-white p-7 shadow-lg md:p-9">
                        <p className="text-sm font-semibold tracking-[0.18em] text-blue-700 uppercase">
                            Department application
                        </p>
                        <h1 className="mt-2 text-3xl font-semibold tracking-tight">
                            Bring your department online
                        </h1>
                        <p className="mt-3 text-sm leading-6 text-slate-600">
                            An administrator will review your application. After
                            approval, sign in with your department email and password.
                        </p>
                        <form
                            onSubmit={(event) => {
                                event.preventDefault();
                                post('/departments/register');
                            }}
                            className="mt-7 space-y-4"
                        >
                            {fields.map((field) => (
                                <label
                                    key={field.key}
                                    className="block text-sm font-medium"
                                >
                                    {field.label}
                                    <input
                                        required
                                        type={field.type}
                                        autoComplete={field.autoComplete}
                                        value={data[field.key]}
                                        onChange={(event) =>
                                            setData(
                                                field.key,
                                                event.target.value,
                                            )
                                        }
                                        className="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-100"
                                    />
                                    {errors[field.key] && (
                                        <span className="mt-1 block text-sm text-red-600">
                                            {errors[field.key]}
                                        </span>
                                    )}
                                </label>
                            ))}
                            <button
                                type="submit"
                                disabled={processing}
                                className="w-full rounded-xl bg-blue-700 px-5 py-3.5 font-semibold text-white transition hover:bg-blue-800 disabled:opacity-50"
                            >
                                {processing
                                    ? 'Submitting…'
                                    : 'Submit for approval'}
                            </button>
                        </form>
                        <p className="mt-5 text-center text-sm text-slate-500">
                            Already approved?{' '}
                            <Link
                                href="/login"
                                className="font-medium text-blue-700"
                            >
                                Sign in
                            </Link>
                        </p>
                    </div>
                </div>
            </main>
        </>
    );
}
