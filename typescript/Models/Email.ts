import { DateTime } from 'luxon'
import {
  BaseModel,
  BelongsTo,
  belongsTo,
  column,
  computed,
  HasMany,
  hasMany,
  HasOne,
  hasOne,
} from '@ioc:Adonis/Lucid/Orm'
import EmailEvent from './EmailEvent'
import User from './User'
import Ping from './Ping'
import ReadEmail from './ReadEmail'
import UnreadEmail from './UnreadEmail'

export default class Email extends BaseModel {
  @column({ isPrimary: true })
  public id: string

  @column()
  public userId: string

  @column()
  public signatureId: string | null

  @column()
  public recipient: string

  @column()
  public subject: string | null

  @column()
  public gmailMessageId: string

  @column()
  public gmailThreadId: string

  @column()
  public isDeleted: boolean = false

  @column.dateTime({ autoCreate: true })
  public createdAt: DateTime

  @column.dateTime({ autoCreate: true, autoUpdate: true })
  public updatedAt: DateTime

  @belongsTo(() => User)
  public user: BelongsTo<typeof User>

  @hasMany(() => EmailEvent)
  public events: HasMany<typeof EmailEvent>

  @hasMany(() => Ping)
  public pings: HasMany<typeof Ping>

  @hasOne(() => ReadEmail)
  public readEmail: HasOne<typeof ReadEmail>

  @hasOne(() => UnreadEmail)
  public unreadEmail: HasOne<typeof UnreadEmail>

  @computed()
  public get date() {
    return this.createdAt.toLocaleString(DateTime.DATE_MED)
  }

  @computed()
  public get time() {
    return this.createdAt.toRelative()
  }

  public get serializedEmailInfo() {
    const { id, recipient, subject, gmailMessageId, gmailThreadId, date, time } = this
    return { id, recipient, subject, gmailMessageId, gmailThreadId, date, time }
  }
}
