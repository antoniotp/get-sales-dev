import React from 'react';

interface LinkifyTextProps {
    text: string;
    className?: string;
}

const LinkifyText: React.FC<LinkifyTextProps> = ({ text, className }) => {
    // Regex to find URLs. It looks for http/https starting sequences.
    const urlRegex = /(\b(?:https?):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/ig;

    // Split the text by the URL regex to get an array of text and URLs
    const parts = text.split(urlRegex);

    return (
        <p className={className}>
            {parts.map((part, index) => {
                // Check if the part of the string is a URL
                if (part && part.match(urlRegex)) {
                    return (
                        <a
                            key={index}
                            href={part}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="text-blue-600 hover:underline dark:text-blue-400"
                        >
                            {part}
                        </a>
                    );
                }
                // Otherwise, just render the text
                return part;
            })}
        </p>
    );
};

export default LinkifyText;
