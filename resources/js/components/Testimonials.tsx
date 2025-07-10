import { Card, CardContent } from "@/components/ui/card";
import { Star } from "lucide-react";

const Testimonials = () => {
  const testimonials = [
    {
      id: 1,
      name: "Michael Johnson",
      role: "Online Store Owner",
      company: "TechHub",
      quote: "Since we implemented GetAlert, our WhatsApp sales increased by 45%. The agents are professional and know our products perfectly.",
      rating: 5,
      image: "https://randomuser.me/api/portraits/men/32.jpg"
    },
    {
      id: 2,
      name: "Sarah Williams",
      role: "Marketing Manager",
      company: "GlowBeauty",
      quote: "The integration was very simple and in less than a week we already had agents responding to inquiries and closing sales. The ROI has been impressive.",
      rating: 5,
      image: "https://randomuser.me/api/portraits/women/44.jpg"
    },
    {
      id: 3,
      name: "David Thompson",
      role: "Commercial Director",
      company: "AutoParts Ltd.",
      quote: "Our clients greatly value the speed of response and the knowledge of the agents. It has improved our brand image and multiplied conversions.",
      rating: 4,
      image: "https://randomuser.me/api/portraits/men/67.jpg"
    }
  ];

  return (
    <section id="testimonios" className="section-padding bg-gray-50">
      <div className="container-custom">
        <div className="text-center max-w-2xl mx-auto mb-16">
          <h2 className="text-3xl md:text-4xl font-bold mb-4 dark:text-gray-950">
            What our <span className="text-brandBlue">customers</span> say
          </h2>
          <p className="text-lg text-gray-600">
            Companies from various sectors have transformed their sales strategy with GetAlert.
          </p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
          {testimonials.map((testimonial) => (
            <Card key={testimonial.id} className="border-none shadow-lg hover:shadow-xl transition-shadow overflow-hidden">
              <CardContent className="px-8 py-2">
                <div className="flex items-center gap-2 mb-4">
                  {[...Array(5)].map((_, i) => (
                    <Star
                      key={i}
                      size={16}
                      className={i < testimonial.rating ? "fill-yellow-400 text-yellow-400" : "text-gray-300"}
                    />
                  ))}
                </div>
                <p className="text-gray-600 mb-6 italic">"{testimonial.quote}"</p>
                <div className="flex items-center gap-4">
                  <img
                    src={testimonial.image}
                    alt={testimonial.name}
                    className="h-12 w-12 rounded-full object-cover"
                  />
                  <div>
                    <h4 className="font-bold dark:text-gray-950">{testimonial.name}</h4>
                    <p className="text-sm text-gray-500">{testimonial.role}, {testimonial.company}</p>
                  </div>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>

        <div className="mt-16 text-center">
          <p className="text-lg font-medium dark:text-gray-700">Companies that trust us:</p>
          <div className="flex flex-wrap justify-center items-center gap-8 mt-6 opacity-70">
            <span className="text-xl font-bold text-gray-400">TechHub</span>
            <span className="text-xl font-bold text-gray-400">GlowBeauty</span>
            <span className="text-xl font-bold text-gray-400">AutoParts</span>
            <span className="text-xl font-bold text-gray-400">StyleMode</span>
            <span className="text-xl font-bold text-gray-400">GreenMarket</span>
          </div>
        </div>
      </div>
    </section>
  );
};

export default Testimonials;
