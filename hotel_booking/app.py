from flask import Flask, request, jsonify
from dataclasses import dataclass, field
from typing import List, Dict
from datetime import date

app = Flask(__name__)

@dataclass
class Room:
    number: int
    type: str
    price: float
    bookings: List[Dict] = field(default_factory=list)

    def is_available(self, check_in: date, check_out: date) -> bool:
        for b in self.bookings:
            if not (check_out <= b['check_in'] or check_in >= b['check_out']):
                return False
        return True

@dataclass
class Hotel:
    rooms: Dict[int, Room] = field(default_factory=dict)

    def add_room(self, number: int, rtype: str, price: float):
        self.rooms[number] = Room(number, rtype, price)

    def find_room(self, number: int) -> Room:
        return self.rooms.get(number)

hotel = Hotel()

@app.post('/rooms')
def create_room():
    data = request.json
    number = data.get('number')
    rtype = data.get('type')
    price = data.get('price')
    if number in hotel.rooms:
        return {'error': 'Room exists'}, 400
    hotel.add_room(number, rtype, price)
    return {'message': 'Room added'}, 201

@app.get('/rooms')
def list_rooms():
    return jsonify([{ 'number': r.number, 'type': r.type, 'price': r.price } for r in hotel.rooms.values()])

@app.post('/book')
def book_room():
    data = request.json
    number = data['number']
    check_in = date.fromisoformat(data['check_in'])
    check_out = date.fromisoformat(data['check_out'])
    room = hotel.find_room(number)
    if not room:
        return {'error': 'Room not found'}, 404
    if not room.is_available(check_in, check_out):
        return {'error': 'Room not available'}, 409
    room.bookings.append({'check_in': check_in, 'check_out': check_out})
    return {'message': 'Room booked'}, 201

@app.get('/availability')
def check_availability():
    number = int(request.args['number'])
    check_in = date.fromisoformat(request.args['check_in'])
    check_out = date.fromisoformat(request.args['check_out'])
    room = hotel.find_room(number)
    if not room:
        return {'error': 'Room not found'}, 404
    return {'available': room.is_available(check_in, check_out)}

if __name__ == '__main__':
    app.run(debug=True)
