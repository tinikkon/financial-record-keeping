/**
 * Отдаёт содержимое браузеру как файл.
 *
 * Ссылка и адрес объекта убираются не сразу, а следующим тиком: браузер
 * забирает содержимое уже после щелчка, и освобождение в ту же строку
 * оставляет файл без имени.
 */
export function saveFile(content: Blob, fileName: string): void {
    const адрес = URL.createObjectURL(content);
    const ссылка = document.createElement('a');

    ссылка.href = адрес;
    ссылка.download = fileName;
    document.body.append(ссылка);
    ссылка.click();

    setTimeout(() => {
        ссылка.remove();
        URL.revokeObjectURL(адрес);
    }, 0);
}
