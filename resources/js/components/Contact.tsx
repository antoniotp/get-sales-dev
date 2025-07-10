import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { useToast } from '@/components/ui/use-toast';
import { Mail, MapPin, MessageSquare, Phone } from 'lucide-react';
import { useState } from 'react';

const Contact = () => {
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
        // Here would go the form submission logic

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
                            Ready to <span className="text-brandBlue">boost your sales</span>?
                        </h2>
                        <p className="mb-8 text-lg text-gray-600">
                            Contact us today for a free demonstration of how GetAlert can transform your business.
                        </p>

                        <div className="mb-8 space-y-6 dark:text-gray-950">
                            <div className="flex items-start gap-4">
                                <div className="rounded-full bg-brandBlue/10 p-3">
                                    <Phone className="h-6 w-6 text-brandBlue" />
                                </div>
                                <div>
                                    <h3 className="text-lg font-bold">Call us</h3>
                                    <p className="text-gray-600">+34 666 632 755</p>
                                </div>
                            </div>

                            <div className="flex items-start gap-4">
                                <div className="rounded-full bg-brandBlue/10 p-3">
                                    <Mail className="h-6 w-6 text-brandBlue" />
                                </div>
                                <div>
                                    <h3 className="text-lg font-bold">Email</h3>
                                    <p className="text-gray-600">getalert@aiscreener.io</p>
                                </div>
                            </div>

                            <div className="flex items-start gap-4">
                                <div className="rounded-full bg-brandBlue/10 p-3">
                                    <MapPin className="h-6 w-6 text-brandBlue" />
                                </div>
                                <div>
                                    <h3 className="text-lg font-bold">Office</h3>
                                    <p className="text-gray-600">Roseti 2052, 4to J (1425), CABA, Buenos Aires, Argentina</p>
                                </div>
                            </div>

                            <div className="flex items-start gap-4">
                                <div className="rounded-full bg-whatsapp/10 p-3">
                                    <MessageSquare className="h-6 w-6 text-whatsapp" />
                                </div>
                                <div>
                                    <h3 className="text-lg font-bold">WhatsApp</h3>
                                    <p className="text-gray-600">+34 666 632 755</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-2xl bg-white p-8 shadow-xl dark:text-gray-950">
                        <h3 className="mb-6 text-2xl font-bold">Send us a message</h3>

                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div>
                                <label htmlFor="name" className="mb-2 block text-sm font-medium">
                                    Full name
                                </label>
                                <Input id="name" name="name" value={formData.name} onChange={handleChange} placeholder="Enter your name" required />
                            </div>

                            <div>
                                <label htmlFor="email" className="mb-2 block text-sm font-medium">
                                    Email
                                </label>
                                <Input
                                    id="email"
                                    name="email"
                                    type="email"
                                    value={formData.email}
                                    onChange={handleChange}
                                    placeholder="example@email.com"
                                    required
                                />
                            </div>

                            <div>
                                <label htmlFor="phone" className="mb-2 block text-sm font-medium">
                                    Phone
                                </label>
                                <Input id="phone" name="phone" value={formData.phone} onChange={handleChange} placeholder="+34 600 000 000" />
                            </div>

                            <div>
                                <label htmlFor="message" className="mb-2 block text-sm font-medium">
                                    Message
                                </label>
                                <Textarea
                                    id="message"
                                    name="message"
                                    value={formData.message}
                                    onChange={handleChange}
                                    placeholder="How can we help you?"
                                    rows={4}
                                    required
                                    className="dark:bg-white dark:text-gray-950"
                                />
                            </div>

                            <Button type="submit" className="w-full bg-brandBlue py-6 hover:bg-brandBlue/90">
                                Send message
                            </Button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    );
};

export default Contact;
