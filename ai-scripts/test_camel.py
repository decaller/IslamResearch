from camel_tools.morphology.database import MorphologyDB
from camel_tools.morphology.analyzer import Analyzer
print('Loading DB...')
db = MorphologyDB.builtin_db()
print('DB Loaded. Initializing Analyzer...')
analyzer = Analyzer(db)
print('Analyzer initialized')
print(analyzer.analyze('كلمة'))
