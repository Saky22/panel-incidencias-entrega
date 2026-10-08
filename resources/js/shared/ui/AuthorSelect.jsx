import { useId } from 'react';
import { inputClass } from '@/shared/ui/Field';

/**
 * Selector de autor (combobox): sugiere los nombres ya usados en la base de datos
 * pero admite escribir uno nuevo, porque no hay autenticación en el alcance.
 * La misma lista alimenta operador, autor de comentario, solicitante y asignado.
 */
export default function AuthorSelect({ value, onChange, authors = [], placeholder, className, autoFocus }) {
    const listId = useId();

    return (
        <>
            <input
                value={value}
                onChange={(e) => onChange(e.target.value)}
                placeholder={placeholder}
                className={inputClass + (className ? ` ${className}` : '')}
                list={listId}
                autoComplete="off"
                autoFocus={autoFocus}
            />
            <datalist id={listId}>
                {authors.map((name) => <option key={name} value={name} />)}
            </datalist>
        </>
    );
}
