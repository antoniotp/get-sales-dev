import { Users, MessageSquare, BarChart3, UserCheck, Clock, ShieldCheck } from "lucide-react";
import { Card, CardContent } from "@/components/ui/card";

const Features = () => {
  const featuresData = [
    {
      icon: <Users className="h-10 w-10 text-brandBlue" />,
      title: "Professional Agents",
      description: "Connect with sales specialists through WhatsApp who will represent your business."
    },
    {
      icon: <MessageSquare className="h-10 w-10 text-whatsapp" />,
      title: "WhatsApp Business Integration",
      description: "Easily connect your WhatsApp Business account and start operating in minutes."
    },
    {
      icon: <Clock className="h-10 w-10 text-brandBlue" />,
      title: "24/7 Support",
      description: "Your customers will receive attention at any time of day, improving their experience."
    },
    {
      icon: <BarChart3 className="h-10 w-10 text-whatsapp" />,
      title: "Detailed Analytics",
      description: "Real-time metrics on conversions, response time, and customer satisfaction."
    },
    {
      icon: <UserCheck className="h-10 w-10 text-brandBlue" />,
      title: "Message Personalization",
      description: "Adapt messages and communication tone according to your brand and customer type."
    },
    {
      icon: <ShieldCheck className="h-10 w-10 text-whatsapp" />,
      title: "Guaranteed Security",
      description: "All conversations are secure and comply with data protection regulations."
    }
  ];

  return (
    <section id="features" className="section-padding bg-gray-50">
      <div className="container-custom">
        <div className="text-center max-w-3xl mx-auto mb-16">
          <h2 className="text-3xl md:text-4xl font-bold mb-4 dark:text-gray-950">
            Everything you need to <span className="text-brandBlue">boost your sales</span>
          </h2>
          <p className="text-lg text-gray-600">
            Our system provides you with all the necessary tools to convert WhatsApp conversations into successful sales.
          </p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
          {featuresData.map((feature, index) => (
            <Card key={index} className="border-none shadow-lg hover:shadow-xl transition-shadow overflow-hidden bg-white">
              <CardContent className="">
                <div className="mb-6">{feature.icon}</div>
                <h3 className="text-xl font-bold mb-2 dark:text-gray-950">{feature.title}</h3>
                <p className="text-gray-600">{feature.description}</p>
              </CardContent>
            </Card>
          ))}
        </div>
      </div>
    </section>
  );
};

export default Features;
