import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { useToast } from '@/components/ui/use-toast';
import { Mail, MapPin, MessageSquare } from 'lucide-react';
import { useState } from 'react';
import { useTranslation, Trans } from "react-i18next";

const Contact = () => {
    const { t } = useTranslation("home");
    const { toast } = useToast();
    const [formData, setFormData] = useState({
        name: '',
        email: '',
        phone: '',
        message: '',
    });

    const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
        const { name, value } = e.target;
        setFormData((prev) => ({ ...prev, [name]: value }));
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        toast({
            title: 'Message sent',
            description: 'We will contact you soon.',
        });

        setFormData({
            name: '',
            email: '',
            phone: '',
            message: '',
        });
    };

    return (
        <section id="contact" className="section-padding bg-white">
            <div className="container-custom">
                <div className="grid grid-cols-1 gap-12 lg:grid-cols-2">
                    <div>
                        <h2 className="mb-6 text-3xl font-bold md:text-4xl dark:text-gray-950">
                            <Trans
                                i18nKey="contact.title"
                                t={t}
                                components={{ highlight: <span className="text-brandRed" /> }}
                            />
                        </h2>
                        <p className="mb-8 text-lg text-gray-600">
                            {t("contact.description")}
                        </p>

                        <div className="mb-8 space-y-6 dark:text-gray-950">
                            <div className="flex items-start gap-4">
                                <div className="rounded-full bg-whatsapp/10 p-3">
                                    <MessageSquare className="h-6 w-6 text-whatsapp" />
                                </div>
                                <div>
                                    <h3 className="text-lg font-bold">WhatsApp</h3>
                                    <p className="text-gray-600">+34 666 632 755</p>
                                </div>
                            </div>

                            <div className="flex items-start gap-4">
                                <div className="rounded-full bg-brandRed/10 p-3">
                                    <Mail className="h-6 w-6 text-brandRed" />
                                </div>
                                <div>
                                    <h3 className="text-lg font-bold">Email</h3>
                                    <p className="text-gray-600">hello@get-sales.com</p>
                                </div>
                            </div>

                            <div className="flex items-start gap-4">
                                <div className="rounded-full bg-brandRed/10 p-3">
                                    <MapPin className="h-6 w-6 text-brandRed" />
                                </div>
                                <div>
                                    <h3 className="text-lg font-bold">{t("contact.channels.office.label")}</h3>
                                    <p className="text-gray-600">{t("contact.channels.office.value")}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-2xl bg-white p-8 shadow-xl dark:text-gray-950">
                        <h3 className="mb-6 text-2xl font-bold">{t("contact.form.title")}</h3>

                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div>
                                <label htmlFor="name" className="mb-2 block text-sm font-medium">
                                    {t("contact.form.fields.name.label")}
                                </label>
                                <Input id="name" name="name" value={formData.name} onChange={handleChange} placeholder={t("contact.form.fields.name.placeholder")} required />
                            </div>

                            <div>
                                <label htmlFor="email" className="mb-2 block text-sm font-medium">
                                    {t("contact.form.fields.email.label")}
                                </label>
                                <Input
                                    id="email"
                                    name="email"
                                    type="email"
                                    value={formData.email}
                                    onChange={handleChange}
                                    placeholder={t("contact.form.fields.email.placeholder")}
                                    required
                                />
                            </div>

                            <div>
                                <label htmlFor="phone" className="mb-2 block text-sm font-medium">
                                    {t("contact.form.fields.phone.label")}
                                </label>
                                <Input id="phone" name="phone" value={formData.phone} onChange={handleChange} placeholder={t("contact.form.fields.phone.placeholder")} />
                            </div>

                            <div>
                                <label htmlFor="message" className="mb-2 block text-sm font-medium">
                                    {t("contact.form.fields.message.label")}
                                </label>
                                <Textarea
                                    id="message"
                                    name="message"
                                    value={formData.message}
                                    onChange={handleChange}
                                    placeholder={t("contact.form.fields.message.placeholder")}
                                    rows={4}
                                    required
                                    className="dark:bg-white dark:text-gray-950"
                                />
                            </div>

                            <Button type="submit" className="w-full bg-brandRed py-6 hover:bg-brandRed/90">
                                {t("contact.form.submit")}
                            </Button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    );
};

export default Contact;
