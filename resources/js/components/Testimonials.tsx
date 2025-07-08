
import { Card, CardContent } from "@/components/ui/card";
import { Star } from "lucide-react";

const Testimonials = () => {
  const testimonials = [
    {
      id: 1,
      name: "Carlos Ramírez",
      role: "Dueño de Tienda Online",
      company: "TecnoShop",
      quote: "Desde que implementamos GetAlert, nuestras ventas por WhatsApp aumentaron un 45%. Los agentes son profesionales y conocen perfectamente nuestros productos.",
      rating: 5,
      image: "https://randomuser.me/api/portraits/men/32.jpg"
    },
    {
      id: 2,
      name: "María González",
      role: "Gerente de Marketing",
      company: "BeautyCare",
      quote: "La integración fue muy sencilla y en menos de una semana ya teníamos agentes respondiendo consultas y cerrando ventas. El ROI ha sido impresionante.",
      rating: 5,
      image: "https://randomuser.me/api/portraits/women/44.jpg"
    },
    {
      id: 3,
      name: "Javier Méndez",
      role: "Director Comercial",
      company: "AutoPartes S.A.",
      quote: "Nuestros clientes valoran enormemente la rapidez de respuesta y el conocimiento de los agentes. Ha mejorado nuestra imagen de marca y multiplicado las conversiones.",
      rating: 4,
      image: "https://randomuser.me/api/portraits/men/67.jpg"
    }
  ];

  return (
    <section id="testimonios" className="section-padding bg-gray-50">
      <div className="container-custom">
        <div className="text-center max-w-2xl mx-auto mb-16">
          <h2 className="text-3xl md:text-4xl font-bold mb-4 dark:text-gray-950">
            Lo que dicen nuestros <span className="text-brandBlue">clientes</span>
          </h2>
          <p className="text-lg text-gray-600">
            Empresas de diversos sectores han transformado su estrategia de ventas con GetAlert.
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
          <p className="text-lg font-medium dark:text-gray-700">Empresas que confían en nosotros:</p>
          <div className="flex flex-wrap justify-center items-center gap-8 mt-6 opacity-70">
            <span className="text-xl font-bold text-gray-400">TecnoShop</span>
            <span className="text-xl font-bold text-gray-400">BeautyCare</span>
            <span className="text-xl font-bold text-gray-400">AutoPartes</span>
            <span className="text-xl font-bold text-gray-400">FashionStyle</span>
            <span className="text-xl font-bold text-gray-400">EcoMarket</span>
          </div>
        </div>
      </div>
    </section>
  );
};

export default Testimonials;
