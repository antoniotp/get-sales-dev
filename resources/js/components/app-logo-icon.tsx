import { ClassAttributes, ImgHTMLAttributes } from "react";

export default function AppLogoIcon(props: ClassAttributes<HTMLImageElement> & ImgHTMLAttributes<HTMLImageElement>) {
    return <img {...props} src="/images/logo.png" alt="GetAlert logo" />;
}
